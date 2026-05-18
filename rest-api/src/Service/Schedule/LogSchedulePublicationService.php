<?php

declare(strict_types=1);

namespace App\Service\Schedule;

use App\Entity\ActionLog;
use App\Entity\Schedule;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final readonly class LogSchedulePublicationService
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function handle(User $user, Schedule $schedule, \DateTimeImmutable $createdAt): void
    {
        $scheduleId = $schedule->getId();

        if ($scheduleId === null) {
            return;
        }

        $this->entityManager->persist(new ActionLog($user, 'schedule.published', 'schedule', $scheduleId, $createdAt));
    }
}
