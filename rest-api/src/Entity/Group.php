<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GroupRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\Table(name: 'groups')]
class Group
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING)]
    private string $name;

    #[ORM\Column(type: Types::STRING)]
    private string $speciality;

    #[ORM\Column(type: Types::INTEGER)]
    private int $course;

    #[ORM\Column(name: 'student_count', type: Types::INTEGER)]
    private int $studentCount;

    public function __construct(
        string $name,
        string $speciality,
        int $course,
        int $studentCount,
    ) {
        $this->name = $name;
        $this->speciality = $speciality;
        $this->course = $course;
        $this->studentCount = $studentCount;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSpeciality(): string
    {
        return $this->speciality;
    }

    public function setSpeciality(string $speciality): void
    {
        $this->speciality = $speciality;
    }

    public function getCourse(): int
    {
        return $this->course;
    }

    public function setCourse(int $course): void
    {
        $this->course = $course;
    }

    public function getStudentCount(): int
    {
        return $this->studentCount;
    }

    public function setStudentCount(int $studentCount): void
    {
        $this->studentCount = $studentCount;
    }
}
