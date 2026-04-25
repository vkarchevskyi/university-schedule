<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LessonGroupRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LessonGroupRepository::class)]
#[ORM\Table(name: 'lesson_groups')]
class LessonGroup
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Lesson::class, inversedBy: 'groups')]
    #[ORM\JoinColumn(name: 'lesson_id', referencedColumnName: 'id', nullable: false)]
    private Lesson $lesson;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Group::class)]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id', nullable: false)]
    private Group $group;

    public function __construct(Lesson $lesson, Group $group)
    {
        $this->lesson = $lesson;
        $this->group = $group;
    }

    public function getLesson(): Lesson
    {
        return $this->lesson;
    }

    public function getGroup(): Group
    {
        return $this->group;
    }
}
