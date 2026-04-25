<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ExamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExamRepository::class)]
#[ORM\Table(name: 'exams')]
class Exam
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Semester::class, inversedBy: 'exams')]
    #[ORM\JoinColumn(name: 'semester_id', referencedColumnName: 'id', nullable: false)]
    private ?Semester $semester;

    #[ORM\ManyToOne(targetEntity: Subject::class)]
    #[ORM\JoinColumn(name: 'subject_id', referencedColumnName: 'id', nullable: false)]
    private Subject $subject;

    #[ORM\ManyToOne(targetEntity: Teacher::class)]
    #[ORM\JoinColumn(name: 'teacher_id', referencedColumnName: 'id', nullable: false)]
    private Teacher $teacher;

    #[ORM\ManyToOne(targetEntity: Room::class)]
    #[ORM\JoinColumn(name: 'room_id', referencedColumnName: 'id', nullable: false)]
    private Room $room;

    #[ORM\Column(name: 'exam_date', type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $examDate;

    #[ORM\Column(name: 'starts_at', type: Types::TIME_IMMUTABLE)]
    private \DateTimeImmutable $startsAt;

    #[ORM\ManyToOne(targetEntity: Admin::class, inversedBy: 'exams')]
    #[ORM\JoinColumn(name: 'created_by', referencedColumnName: 'id', nullable: false)]
    private Admin $createdBy;

    /** @var Collection<int, ExamGroup> */
    #[ORM\OneToMany(targetEntity: ExamGroup::class, mappedBy: 'exam', cascade: ['persist', 'remove'])]
    private Collection $groups;

    public function __construct(
        Semester $semester,
        Subject $subject,
        Teacher $teacher,
        Room $room,
        \DateTimeImmutable $examDate,
        \DateTimeImmutable $startsAt,
        Admin $createdBy,
    ) {
        $this->semester = $semester;
        $this->subject = $subject;
        $this->teacher = $teacher;
        $this->room = $room;
        $this->examDate = $examDate;
        $this->startsAt = $startsAt;
        $this->createdBy = $createdBy;
        $this->groups = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSemester(): ?Semester
    {
        return $this->semester;
    }

    public function setSemester(?Semester $semester): void
    {
        $this->semester = $semester;
    }

    public function getSubject(): Subject
    {
        return $this->subject;
    }

    public function setSubject(Subject $subject): void
    {
        $this->subject = $subject;
    }

    public function getTeacher(): Teacher
    {
        return $this->teacher;
    }

    public function setTeacher(Teacher $teacher): void
    {
        $this->teacher = $teacher;
    }

    public function getRoom(): Room
    {
        return $this->room;
    }

    public function setRoom(Room $room): void
    {
        $this->room = $room;
    }

    public function getExamDate(): \DateTimeImmutable
    {
        return $this->examDate;
    }

    public function setExamDate(\DateTimeImmutable $examDate): void
    {
        $this->examDate = $examDate;
    }

    public function getStartsAt(): \DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function setStartsAt(\DateTimeImmutable $startsAt): void
    {
        $this->startsAt = $startsAt;
    }

    public function getCreatedBy(): Admin
    {
        return $this->createdBy;
    }

    public function setCreatedBy(Admin $createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    /** @return Collection<int, ExamGroup> */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(ExamGroup $group): void
    {
        if (!$this->groups->contains($group)) {
            $this->groups->add($group);
        }
    }

    public function removeGroup(ExamGroup $group): void
    {
        $this->groups->removeElement($group);
    }
}
