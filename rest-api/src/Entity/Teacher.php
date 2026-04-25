<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TeacherRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeacherRepository::class)]
#[ORM\Table(name: 'teachers')]
class Teacher
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(name: 'first_name', type: Types::STRING)]
    private string $firstName;

    #[ORM\Column(name: 'last_name', type: Types::STRING)]
    private string $lastName;

    #[ORM\Column(type: Types::STRING)]
    private string $department;

    /** @var Collection<int, TeacherUnavailability> */
    #[ORM\OneToMany(targetEntity: TeacherUnavailability::class, mappedBy: 'teacher', cascade: ['persist', 'remove'])]
    private Collection $unavailabilities;

    /** @var Collection<int, TeacherSubject> */
    #[ORM\OneToMany(targetEntity: TeacherSubject::class, mappedBy: 'teacher', cascade: ['persist', 'remove'])]
    private Collection $teacherSubjects;

    public function __construct(
        string $firstName,
        string $lastName,
        string $department,
    ) {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->department = $department;
        $this->unavailabilities = new ArrayCollection();
        $this->teacherSubjects = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getDepartment(): string
    {
        return $this->department;
    }

    public function setDepartment(string $department): void
    {
        $this->department = $department;
    }

    /** @return Collection<int, TeacherUnavailability> */
    public function getUnavailabilities(): Collection
    {
        return $this->unavailabilities;
    }

    /** @return Collection<int, TeacherSubject> */
    public function getTeacherSubjects(): Collection
    {
        return $this->teacherSubjects;
    }
}
