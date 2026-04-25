<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TelegramSubscriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TelegramSubscriptionRepository::class)]
#[ORM\Table(name: 'telegram_subscriptions')]
class TelegramSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(name: 'telegram_chat_id', type: Types::BIGINT)]
    private int $telegramChatId;

    #[ORM\Column(name: 'entity_type', type: Types::STRING)]
    private string $entityType;

    #[ORM\Column(name: 'entity_id', type: Types::BIGINT)]
    private int $entityId;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        int $telegramChatId,
        string $entityType,
        int $entityId,
        \DateTimeImmutable $createdAt,
    ) {
        $this->telegramChatId = $telegramChatId;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->createdAt = $createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTelegramChatId(): int
    {
        return $this->telegramChatId;
    }

    public function setTelegramChatId(int $telegramChatId): void
    {
        $this->telegramChatId = $telegramChatId;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function setEntityType(string $entityType): void
    {
        $this->entityType = $entityType;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function setEntityId(int $entityId): void
    {
        $this->entityId = $entityId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
