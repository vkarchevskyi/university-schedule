<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\TelegramSubscription;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ListTelegramSubscriptionsService
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    /** @return list<TelegramSubscription> */
    public function list(string $type, int $entityId): array
    {
        return array_values($this->entityManager->getRepository(TelegramSubscription::class)->findBy([
            'entityType' => $type,
            'entityId' => $entityId,
        ]));
    }
}
