<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\WeekParity;
use App\Repository\ScheduleEntryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScheduleEntryRepository::class)]
#[ORM\Table(name: 'schedule_entries')]
class ScheduleEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Schedule::class, inversedBy: 'entries')]
    #[ORM\JoinColumn(name: 'schedule_id', referencedColumnName: 'id', nullable: false)]
    private ?Schedule $schedule;

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

    #[ORM\Column(name: 'day_of_week', type: Types::SMALLINT)]
    private int $dayOfWeek;

    #[ORM\Column(name: 'week_parity', type: Types::STRING, enumType: WeekParity::class)]
    private WeekParity $weekParity;

    /** @var Collection<int, ScheduleEntryGroup> */
    #[ORM\OneToMany(targetEntity: ScheduleEntryGroup::class, mappedBy: 'scheduleEntry', cascade: ['persist', 'remove'])]
    private Collection $groups;

    /** @var Collection<int, Lesson> */
    #[ORM\OneToMany(targetEntity: Lesson::class, mappedBy: 'scheduleEntry')]
    private Collection $lessons;

    public function __construct(
        Schedule $schedule,
        Subject $subject,
        Teacher $teacher,
        Room $room,
        TimeSlot $timeSlot,
        int $dayOfWeek,
        WeekParity $weekParity,
    ) {
        $this->schedule = $schedule;
        $this->subject = $subject;
        $this->teacher = $teacher;
        $this->room = $room;
        $this->timeSlot = $timeSlot;
        $this->dayOfWeek = $dayOfWeek;
        $this->weekParity = $weekParity;
        $this->groups = new ArrayCollection();
        $this->lessons = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSchedule(): ?Schedule
    {
        return $this->schedule;
    }

    public function setSchedule(?Schedule $schedule): void
    {
        $this->schedule = $schedule;
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

    public function getDayOfWeek(): int
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(int $dayOfWeek): void
    {
        $this->dayOfWeek = $dayOfWeek;
    }

    public function getWeekParity(): WeekParity
    {
        return $this->weekParity;
    }

    public function setWeekParity(WeekParity $weekParity): void
    {
        $this->weekParity = $weekParity;
    }

    /** @return Collection<int, ScheduleEntryGroup> */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(ScheduleEntryGroup $group): void
    {
        if (!$this->groups->contains($group)) {
            $this->groups->add($group);
        }
    }

    public function removeGroup(ScheduleEntryGroup $group): void
    {
        $this->groups->removeElement($group);
    }

    /** @return Collection<int, Lesson> */
    public function getLessons(): Collection
    {
        return $this->lessons;
    }
}
