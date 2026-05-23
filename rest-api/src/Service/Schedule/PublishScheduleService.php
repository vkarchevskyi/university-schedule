<?php

declare(strict_types=1);

namespace App\Service\Schedule;

use App\Entity\Schedule;
use App\Enum\ScheduleStatus;
use App\Exception\ApiException;
use App\Resource\Admin\ScheduleResource;
use App\Resource\Admin\ScheduleResourceMapper;
use App\Service\AbstractEntityService;
use App\Service\ScheduleValidation\ValidateScheduleService;
use App\Service\Telegram\PublishScheduleNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class PublishScheduleService extends AbstractEntityService
{
    public function __construct(
        private readonly ValidateScheduleService $validator,
        private readonly ScheduleResourceMapper $mapper,
        private readonly ScheduleAuditLoggerService $auditLogger,
        private readonly PublishScheduleNotificationService $notifications,
        private readonly LoggerInterface $logger,
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($entityManager);
    }

    public function handle(int $id): ScheduleResource
    {
        $schedule = $this->getEntity(Schedule::class, $id);

        if ($schedule->getStatus() !== ScheduleStatus::Draft) {
            throw ApiException::validation(['status' => 'Only draft schedules can be published.']);
        }

        $validation = $this->validator->handle($id);

        if (!$validation->valid) {
            throw ApiException::http([
                'valid' => false,
                'conflicts' => $validation->conflicts,
            ], 422);
        }

        $publishedAt = new \DateTimeImmutable();
        $beforePayload = $this->auditLogger->schedulePayload($schedule);
        $resource = $this->entityManager->wrapInTransaction(function () use ($schedule, $publishedAt, $beforePayload): ScheduleResource {
            $schedule->publish($publishedAt);
            $this->auditLogger->logSchedulePublished($schedule, $beforePayload);

            return $this->mapper->map($schedule);
        });

        try {
            $this->notifications->handle($schedule);
        } catch (\Throwable $exception) {
            $this->logger->warning('Failed to publish schedule notification.', [
                'exception' => $exception,
                'scheduleId' => $schedule->getId(),
            ]);
        }

        return $resource;
    }
}
