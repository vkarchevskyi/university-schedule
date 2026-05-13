<?php

declare(strict_types=1);

namespace App\Service\LessonCard;

use App\Entity\Schedule;
use App\Entity\ScheduleEntryTeachingLoad;
use App\Entity\TeachingLoad;
use App\Enum\WeekParity;
use App\Resource\Admin\LessonCardResourceMapper;
use App\Resource\Admin\ResourceCollection;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class ListLessonCardsService extends AbstractEntityService
{
    public function __construct(private readonly LessonCardResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function list(int $scheduleId): ResourceCollection
    {
        $schedule = $this->getEntity(Schedule::class, $scheduleId);
        $semester = $schedule->getSemester();

        if ($semester === null) {
            throw new \LogicException('Schedule must belong to a semester.');
        }

        $cards = [];

        foreach ($semester->getTeachingLoads() as $teachingLoad) {
            if ($teachingLoad->getDeletedAt() !== null) {
                continue;
            }

            $cards[] = $this->mapper->map($teachingLoad, $this->scheduledLessonCount($schedule, $teachingLoad));
        }

        return new ResourceCollection($cards);
    }

    private function scheduledLessonCount(Schedule $schedule, TeachingLoad $teachingLoad): int
    {
        $count = 0;

        foreach ($schedule->getEntries() as $entry) {
            foreach ($entry->getTeachingLoads() as $entryTeachingLoad) {
                if ($this->sameTeachingLoad($entryTeachingLoad, $teachingLoad)) {
                    $count += $this->weekParityCount($entry->getWeekParity());
                }
            }
        }

        return $count;
    }

    private function sameTeachingLoad(ScheduleEntryTeachingLoad $entryTeachingLoad, TeachingLoad $teachingLoad): bool
    {
        return $entryTeachingLoad->getTeachingLoad() === $teachingLoad;
    }

    private function weekParityCount(WeekParity $weekParity): int
    {
        return match ($weekParity) {
            WeekParity::Both => 2,
            WeekParity::Odd, WeekParity::Even => 1,
        };
    }
}
