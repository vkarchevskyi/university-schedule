<?php

declare(strict_types=1);

namespace App\Service\ScheduleValidation;

use App\Entity\Schedule;
use App\Resource\Admin\ScheduleValidationResource;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class ValidateScheduleService extends AbstractEntityService
{
    public function __construct(
        private readonly BuildScheduleValidationPayloadService $payloads,
        private readonly ScheduleValidationClientInterface $client,
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($entityManager);
    }

    public function handle(int $id): ScheduleValidationResource
    {
        $schedule = $this->getEntity(Schedule::class, $id);

        return $this->client->validate($this->payloads->handle($schedule));
    }
}
