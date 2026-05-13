<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\LessonType;
use App\Repository\TeachingLoadRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeachingLoadRepository::class)]
#[ORM\Table(name: 'teaching_loads')]
class TeachingLoad
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Semester::class, inversedBy: 'teachingLoads')]
    #[ORM\JoinColumn(name: 'semester_id', referencedColumnName: 'id', nullable: false)]
    private Semester $semester;

    #[ORM\ManyToOne(targetEntity: Group::class, inversedBy: 'teachingLoads')]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id', nullable: false)]
    private Group $group;

    #[ORM\ManyToOne(targetEntity: Subject::class, inversedBy: 'teachingLoads')]
    #[ORM\JoinColumn(name: 'subject_id', referencedColumnName: 'id', nullable: false)]
    private Subject $subject;

    #[ORM\ManyToOne(targetEntity: Teacher::class, inversedBy: 'teachingLoads')]
    #[ORM\JoinColumn(name: 'teacher_id', referencedColumnName: 'id', nullable: false)]
    private Teacher $teacher;

    #[ORM\Column(name: 'lesson_type', type: Types::SMALLINT, enumType: LessonType::class)]
    private LessonType $lessonType;

    #[ORM\Column(name: 'required_lesson_count', type: Types::INTEGER)]
    private int $requiredLessonCount;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(name: 'deleted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deletedAt;

    /** @var Collection<int, ScheduleEntryTeachingLoad> */
    #[ORM\OneToMany(targetEntity: ScheduleEntryTeachingLoad::class, mappedBy: 'teachingLoad', cascade: ['persist', 'remove'])]
    private Collection $scheduleEntries;

    public function __construct(
        Semester $semester,
        Group $group,
        Subject $subject,
        Teacher $teacher,
        LessonType $lessonType,
        int $requiredLessonCount,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?\DateTimeImmutable $deletedAt = null,
    ) {
        $this->semester = $semester;
        $this->group = $group;
        $this->subject = $subject;
        $this->teacher = $teacher;
        $this->lessonType = $lessonType;
        $this->requiredLessonCount = $requiredLessonCount;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->deletedAt = $deletedAt;
        $this->scheduleEntries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSemester(): Semester
    {
        return $this->semester;
    }

    public function setSemester(Semester $semester): void
    {
        $this->semester = $semester;
    }

    public function getGroup(): Group
    {
        return $this->group;
    }

    public function setGroup(Group $group): void
    {
        $this->group = $group;
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

    public function getLessonType(): LessonType
    {
        return $this->lessonType;
    }

    public function setLessonType(LessonType $lessonType): void
    {
        $this->lessonType = $lessonType;
    }

    public function getRequiredLessonCount(): int
    {
        return $this->requiredLessonCount;
    }

    public function setRequiredLessonCount(int $requiredLessonCount): void
    {
        $this->requiredLessonCount = $requiredLessonCount;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    /** @return Collection<int, ScheduleEntryTeachingLoad> */
    public function getScheduleEntries(): Collection
    {
        return $this->scheduleEntries;
    }
}
