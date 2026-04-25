<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AdminRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdminRepository::class)]
#[ORM\Table(name: 'admins')]
class Admin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(name: 'first_name', type: Types::STRING)]
    private string $firstName;

    #[ORM\Column(name: 'last_name', type: Types::STRING)]
    private string $lastName;

    #[ORM\Column(type: Types::STRING, unique: true)]
    private string $email;

    #[ORM\Column(name: 'password_hash', type: Types::STRING)]
    private string $passwordHash;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, Schedule> */
    #[ORM\OneToMany(targetEntity: Schedule::class, mappedBy: 'createdBy')]
    private Collection $schedules;

    /** @var Collection<int, Exam> */
    #[ORM\OneToMany(targetEntity: Exam::class, mappedBy: 'createdBy')]
    private Collection $exams;

    /** @var Collection<int, ActionLog> */
    #[ORM\OneToMany(targetEntity: ActionLog::class, mappedBy: 'admin', cascade: ['persist', 'remove'])]
    private Collection $actionLogs;

    public function __construct(
        string $firstName,
        string $lastName,
        string $email,
        string $passwordHash,
        \DateTimeImmutable $createdAt,
    ) {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->createdAt = $createdAt;
        $this->schedules = new ArrayCollection();
        $this->exams = new ArrayCollection();
        $this->actionLogs = new ArrayCollection();
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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, Schedule> */
    public function getSchedules(): Collection
    {
        return $this->schedules;
    }

    /** @return Collection<int, Exam> */
    public function getExams(): Collection
    {
        return $this->exams;
    }

    /** @return Collection<int, ActionLog> */
    public function getActionLogs(): Collection
    {
        return $this->actionLogs;
    }
}
