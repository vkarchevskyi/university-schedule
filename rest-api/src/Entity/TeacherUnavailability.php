<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TeacherUnavailabilityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeacherUnavailabilityRepository::class)]
#[ORM\Table(name: 'teacher_unavailability')]
class TeacherUnavailability
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Teacher::class, inversedBy: 'unavailabilities')]
    #[ORM\JoinColumn(name: 'teacher_id', referencedColumnName: 'id', nullable: false)]
    private Teacher $teacher;

    #[ORM\Column(name: 'day_of_week', type: Types::SMALLINT)]
    private int $dayOfWeek;

    #[ORM\Column(name: 'unavailable_from', type: Types::TIME_IMMUTABLE)]
    private \DateTimeImmutable $unavailableFrom;

    #[ORM\Column(name: 'unavailable_to', type: Types::TIME_IMMUTABLE)]
    private \DateTimeImmutable $unavailableTo;

    public function __construct(
        Teacher $teacher,
        int $dayOfWeek,
        \DateTimeImmutable $unavailableFrom,
        \DateTimeImmutable $unavailableTo,
    ) {
        $this->teacher = $teacher;
        $this->dayOfWeek = $dayOfWeek;
        $this->unavailableFrom = $unavailableFrom;
        $this->unavailableTo = $unavailableTo;
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

    public function getDayOfWeek(): int
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(int $dayOfWeek): void
    {
        $this->dayOfWeek = $dayOfWeek;
    }

    public function getUnavailableFrom(): \DateTimeImmutable
    {
        return $this->unavailableFrom;
    }

    public function setUnavailableFrom(\DateTimeImmutable $unavailableFrom): void
    {
        $this->unavailableFrom = $unavailableFrom;
    }

    public function getUnavailableTo(): \DateTimeImmutable
    {
        return $this->unavailableTo;
    }

    public function setUnavailableTo(\DateTimeImmutable $unavailableTo): void
    {
        $this->unavailableTo = $unavailableTo;
    }
}
