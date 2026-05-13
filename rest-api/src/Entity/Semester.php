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

    #[ORM\Column(name: 'first_week_parity', type: Types::SMALLINT, enumType: WeekParity::class)]
    private WeekParity $firstWeekParity;

    /** @var Collection<int, Schedule> */
    #[ORM\OneToMany(targetEntity: Schedule::class, mappedBy: 'semester', cascade: ['persist', 'remove'])]
    private Collection $schedules;

    /** @var Collection<int, ExamSchedule> */
    #[ORM\OneToMany(targetEntity: ExamSchedule::class, mappedBy: 'semester', cascade: ['persist', 'remove'])]
    private Collection $examSchedules;

    /** @var Collection<int, TeachingLoad> */
    #[ORM\OneToMany(targetEntity: TeachingLoad::class, mappedBy: 'semester', cascade: ['persist', 'remove'])]
    private Collection $teachingLoads;

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
        $this->examSchedules = new ArrayCollection();
        $this->teachingLoads = new ArrayCollection();
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

    /** @return Collection<int, ExamSchedule> */
    public function getExamSchedules(): Collection
    {
        return $this->examSchedules;
    }

    public function addExamSchedule(ExamSchedule $examSchedule): void
    {
        if (!$this->examSchedules->contains($examSchedule)) {
            $this->examSchedules->add($examSchedule);
            $examSchedule->setSemester($this);
        }
    }

    public function removeExamSchedule(ExamSchedule $examSchedule): void
    {
        if ($this->examSchedules->removeElement($examSchedule)) {
            if ($examSchedule->getSemester() === $this) {
                $examSchedule->setSemester(null);
            }
        }
    }

    /** @return Collection<int, TeachingLoad> */
    public function getTeachingLoads(): Collection
    {
        return $this->teachingLoads;
    }
}
