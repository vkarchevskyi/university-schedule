<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ScheduleEntryGroupRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScheduleEntryGroupRepository::class)]
#[ORM\Table(name: 'schedule_entry_groups')]
class ScheduleEntryGroup
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: ScheduleEntry::class, inversedBy: 'groups')]
    #[ORM\JoinColumn(name: 'schedule_entry_id', referencedColumnName: 'id', nullable: false)]
    private ScheduleEntry $scheduleEntry;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Group::class)]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id', nullable: false)]
    private Group $group;

    public function __construct(ScheduleEntry $scheduleEntry, Group $group)
    {
        $this->scheduleEntry = $scheduleEntry;
        $this->group = $group;
    }

    public function getScheduleEntry(): ScheduleEntry
    {
        return $this->scheduleEntry;
    }

    public function getGroup(): Group
    {
        return $this->group;
    }
}
