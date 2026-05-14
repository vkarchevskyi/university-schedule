<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\TelegramSubscription;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CreateTelegramSubscriptionService
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function handle(int $chatId, TelegramTarget $target): bool
    {
        $existing = $this->entityManager->getRepository(TelegramSubscription::class)->findOneBy([
            'telegramChatId' => $chatId,
            'entityType' => $target->type,
            'entityId' => $target->id,
        ]);

        if ($existing instanceof TelegramSubscription) {
            return false;
        }

        $this->entityManager->persist(new TelegramSubscription($chatId, $target->type, $target->id, new \DateTimeImmutable()));
        $this->entityManager->flush();

        return true;
    }
}
