<?php

declare(strict_types=1);

namespace App\Service\ExamSchedule;

use App\Entity\ExamSchedule;
use App\Exception\ApiException;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DeleteExamScheduleService
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function handle(int $id): void
    {
        $schedule = $this->entityManager->find(ExamSchedule::class, $id);

        if (!$schedule instanceof ExamSchedule || $schedule->getDeletedAt() !== null) {
            throw ApiException::notFound();
        }

        $now = new \DateTimeImmutable();
        $schedule->setDeletedAt($now);

        foreach ($schedule->getEntries() as $entry) {
            if ($entry->getDeletedAt() === null) {
                $entry->setDeletedAt($now);
            }
        }

        $this->entityManager->flush();
    }
}
