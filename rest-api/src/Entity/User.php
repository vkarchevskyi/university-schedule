<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\UserRole;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
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

    #[ORM\Column(type: Types::STRING, enumType: UserRole::class)]
    private UserRole $role;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, Schedule> */
    #[ORM\OneToMany(targetEntity: Schedule::class, mappedBy: 'createdBy')]
    private Collection $schedules;

    /** @var Collection<int, ExamSchedule> */
    #[ORM\OneToMany(targetEntity: ExamSchedule::class, mappedBy: 'createdBy')]
    private Collection $examSchedules;

    /** @var Collection<int, ActionLog> */
    #[ORM\OneToMany(targetEntity: ActionLog::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $actionLogs;

    public function __construct(
        string $firstName,
        string $lastName,
        string $email,
        string $passwordHash,
        \DateTimeImmutable $createdAt,
        UserRole $role = UserRole::User,
    ) {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->createdAt = $createdAt;
        $this->role = $role;
        $this->schedules = new ArrayCollection();
        $this->examSchedules = new ArrayCollection();
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

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function setRole(UserRole $role): void
    {
        $this->role = $role;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUserIdentifier(): string
    {
        if ($this->email === '') {
            throw new \LogicException('User email must not be empty.');
        }

        return $this->email;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];

        if ($this->role === UserRole::Admin) {
            $roles[] = 'ROLE_ADMIN';
        }

        return $roles;
    }

    public function eraseCredentials(): void {}

    /** @return Collection<int, Schedule> */
    public function getSchedules(): Collection
    {
        return $this->schedules;
    }

    /** @return Collection<int, ExamSchedule> */
    public function getExamSchedules(): Collection
    {
        return $this->examSchedules;
    }

    /** @return Collection<int, ActionLog> */
    public function getActionLogs(): Collection
    {
        return $this->actionLogs;
    }
}
