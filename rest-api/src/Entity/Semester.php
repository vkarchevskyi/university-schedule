<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\WeekParity;
use App\Repository\SemesterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SemesterRepository::class)]
#[ORM\Table(name: 'semesters')]
class Semester
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AcademicYear::class, inversedBy: 'semesters')]
    #[ORM\JoinColumn(name: 'academic_year_id', referencedColumnName: 'id', nullable: false)]
    private ?AcademicYear $academicYear;

    #[ORM\Column(type: Types::INTEGER)]
    private int $number;

    #[ORM\Column(name: 'starts_at', type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $startsAt;

    #[ORM\Column(name: 'ends_at', type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $endsAt;

    #[ORM\Column(name: 'first_week_parity', type: Types::STRING, enumType: WeekParity::class)]
    private WeekParity $firstWeekParity;

    /** @var Collection<int, Schedule> */
    #[ORM\OneToMany(targetEntity: Schedule::class, mappedBy: 'semester', cascade: ['persist', 'remove'])]
    private Collection $schedules;

    /** @var Collection<int, Exam> */
    #[ORM\OneToMany(targetEntity: Exam::class, mappedBy: 'semester', cascade: ['persist', 'remove'])]
    private Collection $exams;

    public function __construct(
        AcademicYear $academicYear,
        int $number,
        \DateTimeImmutable $startsAt,
        \DateTimeImmutable $endsAt,
        WeekParity $firstWeekParity,
    ) {
        $this->academicYear = $academicYear;
        $this->number = $number;
        $this->startsAt = $startsAt;
        $this->endsAt = $endsAt;
        $this->firstWeekParity = $firstWeekParity;
        $this->schedules = new ArrayCollection();
        $this->exams = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAcademicYear(): ?AcademicYear
    {
        return $this->academicYear;
    }

    public function setAcademicYear(?AcademicYear $academicYear): void
    {
        $this->academicYear = $academicYear;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): void
    {
        $this->number = $number;
    }

    public function getStartsAt(): \DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function setStartsAt(\DateTimeImmutable $startsAt): void
    {
        $this->startsAt = $startsAt;
    }

    public function getEndsAt(): \DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function setEndsAt(\DateTimeImmutable $endsAt): void
    {
        $this->endsAt = $endsAt;
    }

    public function getFirstWeekParity(): WeekParity
    {
        return $this->firstWeekParity;
    }

    public function setFirstWeekParity(WeekParity $firstWeekParity): void
    {
        $this->firstWeekParity = $firstWeekParity;
    }

    /** @return Collection<int, Schedule> */
    public function getSchedules(): Collection
    {
        return $this->schedules;
    }

    public function addSchedule(Schedule $schedule): void
    {
        if (!$this->schedules->contains($schedule)) {
            $this->schedules->add($schedule);
            $schedule->setSemester($this);
        }
    }

    public function removeSchedule(Schedule $schedule): void
    {
        if ($this->schedules->removeElement($schedule)) {
            if ($schedule->getSemester() === $this) {
                $schedule->setSemester(null);
            }
        }
    }

    /** @return Collection<int, Exam> */
    public function getExams(): Collection
    {
        return $this->exams;
    }

    public function addExam(Exam $exam): void
    {
        if (!$this->exams->contains($exam)) {
            $this->exams->add($exam);
            $exam->setSemester($this);
        }
    }

    public function removeExam(Exam $exam): void
    {
        if ($this->exams->removeElement($exam)) {
            if ($exam->getSemester() === $this) {
                $exam->setSemester(null);
            }
        }
    }
}
