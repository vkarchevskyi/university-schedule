<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ActionLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActionLogRepository::class)]
#[ORM\Table(name: 'action_log')]
class ActionLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'actionLogs')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\Column(type: Types::STRING)]
    private string $action;

    #[ORM\Column(name: 'entity_type', type: Types::STRING)]
    private string $entityType;

    #[ORM\Column(name: 'entity_id', type: Types::BIGINT)]
    private int $entityId;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'before_payload', type: Types::JSON, nullable: true)]
    private ?array $beforePayload;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'after_payload', type: Types::JSON, nullable: true)]
    private ?array $afterPayload;

    /**
     * @param array<string, mixed>|null $beforePayload
     * @param array<string, mixed>|null $afterPayload
     */
    public function __construct(
        User $user,
        string $action,
        string $entityType,
        int $entityId,
        \DateTimeImmutable $createdAt,
        ?array $beforePayload = null,
        ?array $afterPayload = null,
    ) {
        $this->user = $user;
        $this->action = $action;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->createdAt = $createdAt;
        $this->beforePayload = $beforePayload;
        $this->afterPayload = $afterPayload;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return array<string, mixed>|null */
    public function getBeforePayload(): ?array
    {
        return $this->beforePayload;
    }

    /** @return array<string, mixed>|null */
    public function getAfterPayload(): ?array
    {
        return $this->afterPayload;
    }
}
