<?php

declare(strict_types=1);

namespace App\Service\Schedule;

use App\Dto\Admin\ScheduleRequestDto;
use App\Entity\Schedule;
use App\Entity\Semester;
use App\Entity\User;
use App\Enum\ScheduleStatus;
use App\Exception\ApiException;
use App\Resource\Admin\ScheduleResource;
use App\Resource\Admin\ScheduleResourceMapper;
use App\Service\AbstractEntityService;
use App\Service\InputNormalizerTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class CreateScheduleService extends AbstractEntityService
{
    use InputNormalizerTrait;

    public function __construct(
        private readonly ScheduleResourceMapper $mapper,
        private readonly ScheduleAuditLoggerService $auditLogger,
        private readonly Security $security,
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($entityManager);
    }

    public function handle(ScheduleRequestDto $data): ScheduleResource
    {
        $semester = $this->getEntity(Semester::class, $this->positiveInt($data->semesterId));
        $validFrom = $this->scheduleDate($data->validFrom);
        $validTo = $this->scheduleDate($data->validTo);
        $this->validateSchedulePeriod($semester, $validFrom, $validTo);

        $schedule = new Schedule(
            $semester,
            ScheduleStatus::Draft,
            $validFrom,
            $validTo,
            $this->currentUser(),
            new \DateTimeImmutable(),
        );
        $this->save($schedule);
        $this->auditLogger->logScheduleCreated($schedule);
        $this->flush();

        return $this->mapper->map($schedule);
    }

    private function currentUser(): User
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw ApiException::http(['error' => 'Authenticated user was not found.'], 401);
        }

        return $user;
    }

    private function scheduleDate(?string $value): \DateTimeImmutable
    {
        if ($value === null) {
            throw ApiException::validation(['date' => 'Expected valid date.']);
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        if (!$date instanceof \DateTimeImmutable) {
            throw ApiException::validation(['date' => 'Expected valid date.']);
        }

        return $date;
    }

    private function validateSchedulePeriod(Semester $semester, \DateTimeImmutable $validFrom, \DateTimeImmutable $validTo): void
    {
        if ($validFrom < $semester->getStartsAt() || $validTo > $semester->getEndsAt()) {
            throw ApiException::validation(['validFrom' => 'Schedule validity period must be within the semester.']);
        }
    }
}
