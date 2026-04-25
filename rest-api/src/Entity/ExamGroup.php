<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ExamGroupRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExamGroupRepository::class)]
#[ORM\Table(name: 'exam_groups')]
class ExamGroup
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Exam::class, inversedBy: 'groups')]
    #[ORM\JoinColumn(name: 'exam_id', referencedColumnName: 'id', nullable: false)]
    private Exam $exam;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Group::class)]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id', nullable: false)]
    private Group $group;

    public function __construct(Exam $exam, Group $group)
    {
        $this->exam = $exam;
        $this->group = $group;
    }

    public function getExam(): Exam
    {
        return $this->exam;
    }

    public function getGroup(): Group
    {
        return $this->group;
    }
}
