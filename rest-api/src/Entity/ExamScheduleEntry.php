<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ExamScheduleEntryType;
use App\Repository\ExamScheduleEntryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExamScheduleEntryRepository::class)]
#[ORM\Table(name: 'exam_schedule_entries')]
class ExamScheduleEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ExamSchedule::class, inversedBy: 'entries')]
    #[ORM\JoinColumn(name: 'exam_schedule_id', referencedColumnName: 'id', nullable: false)]
    private ?ExamSchedule $examSchedule;

    #[ORM\Column(type: Types::SMALLINT, enumType: ExamScheduleEntryType::class)]
    private ExamScheduleEntryType $type;

    #[ORM\ManyToOne(targetEntity: Subject::class)]
    #[ORM\JoinColumn(name: 'subject_id', referencedColumnName: 'id', nullable: false)]
    private Subject $subject;

    #[ORM\ManyToOne(targetEntity: Teacher::class)]
    #[ORM\JoinColumn(name: 'teacher_id', referencedColumnName: 'id', nullable: false)]
    private Teacher $teacher;

    #[ORM\ManyToOne(targetEntity: Room::class)]
    #[ORM\JoinColumn(name: 'room_id', referencedColumnName: 'id', nullable: false)]
    private Room $room;

    #[ORM\Column(name: 'entry_date', type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $entryDate;

    #[ORM\Column(name: 'starts_at', type: Types::TIME_IMMUTABLE)]
    private \DateTimeImmutable $startsAt;

    #[ORM\Column(name: 'deleted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deletedAt;

    /** @var Collection<int, ExamScheduleEntryGroup> */
    #[ORM\OneToMany(targetEntity: ExamScheduleEntryGroup::class, mappedBy: 'examScheduleEntry', cascade: ['persist', 'remove'])]
    private Collection $groups;

    public function __construct(
        ExamSchedule $examSchedule,
        ExamScheduleEntryType $type,
        Subject $subject,
        Teacher $teacher,
        Room $room,
        \DateTimeImmutable $entryDate,
        \DateTimeImmutable $startsAt,
        ?\DateTimeImmutable $deletedAt = null,
    ) {
        $this->examSchedule = $examSchedule;
        $this->type = $type;
        $this->subject = $subject;
        $this->teacher = $teacher;
        $this->room = $room;
        $this->entryDate = $entryDate;
        $this->startsAt = $startsAt;
        $this->deletedAt = $deletedAt;
        $this->groups = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExamSchedule(): ?ExamSchedule
    {
        return $this->examSchedule;
    }

    public function setExamSchedule(?ExamSchedule $examSchedule): void
    {
        $this->examSchedule = $examSchedule;
    }

    public function getType(): ExamScheduleEntryType
    {
        return $this->type;
    }

    public function setType(ExamScheduleEntryType $type): void
    {
        $this->type = $type;
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

    public function getEntryDate(): \DateTimeImmutable
    {
        return $this->entryDate;
    }

    public function setEntryDate(\DateTimeImmutable $entryDate): void
    {
        $this->entryDate = $entryDate;
    }

    public function getStartsAt(): \DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function setStartsAt(\DateTimeImmutable $startsAt): void
    {
        $this->startsAt = $startsAt;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    /** @return Collection<int, ExamScheduleEntryGroup> */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(ExamScheduleEntryGroup $group): void
    {
        if (!$this->groups->contains($group)) {
            $this->groups->add($group);
        }
    }

    public function removeGroup(ExamScheduleEntryGroup $group): void
    {
        $this->groups->removeElement($group);
    }

    public function clearGroups(): void
    {
        $this->groups->clear();
    }
}
