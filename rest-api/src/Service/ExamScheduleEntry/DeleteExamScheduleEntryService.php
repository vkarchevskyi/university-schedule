<?php

declare(strict_types=1);

namespace App\Service\ExamScheduleEntry;

use App\Entity\ExamSchedule;
use App\Entity\ExamScheduleEntry;
use App\Exception\ApiException;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DeleteExamScheduleEntryService
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function handle(int $scheduleId, int $entryId): void
    {
        $schedule = $this->entityManager->find(ExamSchedule::class, $scheduleId);
        $entry = $this->entityManager->find(ExamScheduleEntry::class, $entryId);

        if (!$schedule instanceof ExamSchedule || $schedule->getDeletedAt() !== null || !$entry instanceof ExamScheduleEntry || $entry->getDeletedAt() !== null || $entry->getExamSchedule() !== $schedule) {
            throw ApiException::notFound();
        }

        $entry->setDeletedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }
}
