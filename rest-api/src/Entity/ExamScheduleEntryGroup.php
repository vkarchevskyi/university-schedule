<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ExamScheduleEntryGroupRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExamScheduleEntryGroupRepository::class)]
#[ORM\Table(name: 'exam_schedule_entry_groups')]
class ExamScheduleEntryGroup
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: ExamScheduleEntry::class, inversedBy: 'groups')]
    #[ORM\JoinColumn(name: 'exam_schedule_entry_id', referencedColumnName: 'id', nullable: false)]
    private ExamScheduleEntry $examScheduleEntry;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Group::class)]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id', nullable: false)]
    private Group $group;

    public function __construct(ExamScheduleEntry $examScheduleEntry, Group $group)
    {
        $this->examScheduleEntry = $examScheduleEntry;
        $this->group = $group;
    }

    public function getExamScheduleEntry(): ExamScheduleEntry
    {
        return $this->examScheduleEntry;
    }

    public function getGroup(): Group
    {
        return $this->group;
    }
}
