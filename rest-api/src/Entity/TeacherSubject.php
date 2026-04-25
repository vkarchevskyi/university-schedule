<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TeacherSubjectRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeacherSubjectRepository::class)]
#[ORM\Table(name: 'teacher_subjects')]
class TeacherSubject
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Teacher::class, inversedBy: 'teacherSubjects')]
    #[ORM\JoinColumn(name: 'teacher_id', referencedColumnName: 'id', nullable: false)]
    private Teacher $teacher;

    #[ORM\ManyToOne(targetEntity: Subject::class, inversedBy: 'teacherSubjects')]
    #[ORM\JoinColumn(name: 'subject_id', referencedColumnName: 'id', nullable: false)]
    private Subject $subject;

    public function __construct(Teacher $teacher, Subject $subject)
    {
        $this->teacher = $teacher;
        $this->subject = $subject;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTeacher(): Teacher
    {
        return $this->teacher;
    }

    public function setTeacher(Teacher $teacher): void
    {
        $this->teacher = $teacher;
    }

    public function getSubject(): Subject
    {
        return $this->subject;
    }

    public function setSubject(Subject $subject): void
    {
        $this->subject = $subject;
    }
}
