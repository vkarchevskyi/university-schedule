<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ScheduleEntryTeachingLoadRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScheduleEntryTeachingLoadRepository::class)]
#[ORM\Table(name: 'schedule_entry_teaching_loads')]
class ScheduleEntryTeachingLoad
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: ScheduleEntry::class, inversedBy: 'teachingLoads')]
    #[ORM\JoinColumn(name: 'schedule_entry_id', referencedColumnName: 'id', nullable: false)]
    private ScheduleEntry $scheduleEntry;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: TeachingLoad::class, inversedBy: 'scheduleEntries')]
    #[ORM\JoinColumn(name: 'teaching_load_id', referencedColumnName: 'id', nullable: false)]
    private TeachingLoad $teachingLoad;

    public function __construct(ScheduleEntry $scheduleEntry, TeachingLoad $teachingLoad)
    {
        $this->scheduleEntry = $scheduleEntry;
        $this->teachingLoad = $teachingLoad;
    }

    public function getScheduleEntry(): ScheduleEntry
    {
        return $this->scheduleEntry;
    }

    public function getTeachingLoad(): TeachingLoad
    {
        return $this->teachingLoad;
    }
}
