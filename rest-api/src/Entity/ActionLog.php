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

    #[ORM\ManyToOne(targetEntity: Admin::class, inversedBy: 'actionLogs')]
    #[ORM\JoinColumn(name: 'admin_id', referencedColumnName: 'id', nullable: false)]
    private Admin $admin;

    #[ORM\Column(type: Types::STRING)]
    private string $action;

    #[ORM\Column(name: 'entity_type', type: Types::STRING)]
    private string $entityType;

    #[ORM\Column(name: 'entity_id', type: Types::BIGINT)]
    private int $entityId;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Admin $admin,
        string $action,
        string $entityType,
        int $entityId,
        \DateTimeImmutable $createdAt,
    ) {
        $this->admin = $admin;
        $this->action = $action;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->createdAt = $createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdmin(): Admin
    {
        return $this->admin;
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
}
