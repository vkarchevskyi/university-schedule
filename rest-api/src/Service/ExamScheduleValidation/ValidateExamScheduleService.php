<?php

declare(strict_types=1);

namespace App\Service\ExamScheduleValidation;

use App\Entity\ExamSchedule;
use App\Exception\ApiException;
use App\Resource\Admin\ExamScheduleValidationResource;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ValidateExamScheduleService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidateExamScheduleRulesService $rules,
    ) {}

    public function handle(int $id): ExamScheduleValidationResource
    {
        $schedule = $this->entityManager->find(ExamSchedule::class, $id);

        if (!$schedule instanceof ExamSchedule || $schedule->getDeletedAt() !== null) {
            throw ApiException::notFound();
        }

        return $this->rules->validate($schedule);
    }
}
