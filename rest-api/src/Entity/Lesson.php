<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LessonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LessonRepository::class)]
#[ORM\Table(name: 'lessons')]
class Lesson
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ScheduleEntry::class, inversedBy: 'lessons')]
    #[ORM\JoinColumn(name: 'schedule_entry_id', referencedColumnName: 'id', nullable: true)]
    private ?ScheduleEntry $scheduleEntry;

    #[ORM\Column(name: 'lesson_date', type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $lessonDate;

    #[ORM\ManyToOne(targetEntity: Subject::class)]
    #[ORM\JoinColumn(name: 'subject_id', referencedColumnName: 'id', nullable: false)]
    private Subject $subject;

    #[ORM\ManyToOne(targetEntity: Teacher::class)]
    #[ORM\JoinColumn(name: 'teacher_id', referencedColumnName: 'id', nullable: false)]
    private Teacher $teacher;

    #[ORM\ManyToOne(targetEntity: Room::class)]
    #[ORM\JoinColumn(name: 'room_id', referencedColumnName: 'id', nullable: false)]
    private Room $room;

    #[ORM\ManyToOne(targetEntity: TimeSlot::class)]
    #[ORM\JoinColumn(name: 'time_slot_id', referencedColumnName: 'id', nullable: false)]
    private TimeSlot $timeSlot;

    #[ORM\Column(name: 'is_cancelled', type: Types::BOOLEAN)]
    private bool $isCancelled;

    #[ORM\Column(name: 'is_override', type: Types::BOOLEAN)]
    private bool $isOverride;

    /** @var Collection<int, LessonGroup> */
    #[ORM\OneToMany(targetEntity: LessonGroup::class, mappedBy: 'lesson', cascade: ['persist', 'remove'])]
    private Collection $groups;

    public function __construct(
        \DateTimeImmutable $lessonDate,
        Subject $subject,
        Teacher $teacher,
        Room $room,
        TimeSlot $timeSlot,
        bool $isCancelled,
        bool $isOverride,
        ?ScheduleEntry $scheduleEntry = null,
    ) {
        $this->lessonDate = $lessonDate;
        $this->subject = $subject;
        $this->teacher = $teacher;
        $this->room = $room;
        $this->timeSlot = $timeSlot;
        $this->isCancelled = $isCancelled;
        $this->isOverride = $isOverride;
        $this->scheduleEntry = $scheduleEntry;
        $this->groups = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getScheduleEntry(): ?ScheduleEntry
    {
        return $this->scheduleEntry;
    }

    public function setScheduleEntry(?ScheduleEntry $scheduleEntry): void
    {
        $this->scheduleEntry = $scheduleEntry;
    }

    public function getLessonDate(): \DateTimeImmutable
    {
        return $this->lessonDate;
    }

    public function setLessonDate(\DateTimeImmutable $lessonDate): void
    {
        $this->lessonDate = $lessonDate;
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

    public function getTimeSlot(): TimeSlot
    {
        return $this->timeSlot;
    }

    public function setTimeSlot(TimeSlot $timeSlot): void
    {
        $this->timeSlot = $timeSlot;
    }

    public function isCancelled(): bool
    {
        return $this->isCancelled;
    }

    public function setIsCancelled(bool $isCancelled): void
    {
        $this->isCancelled = $isCancelled;
    }

    public function isOverride(): bool
    {
        return $this->isOverride;
    }

    public function setIsOverride(bool $isOverride): void
    {
        $this->isOverride = $isOverride;
    }

    /** @return Collection<int, LessonGroup> */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(LessonGroup $group): void
    {
        if (!$this->groups->contains($group)) {
            $this->groups->add($group);
        }
    }

    public function removeGroup(LessonGroup $group): void
    {
        $this->groups->removeElement($group);
    }
}
