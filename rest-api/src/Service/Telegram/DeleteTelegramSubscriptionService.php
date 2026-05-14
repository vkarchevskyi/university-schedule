<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\TelegramSubscription;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DeleteTelegramSubscriptionService
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function handle(int $chatId, TelegramTarget $target): bool
    {
        $subscription = $this->entityManager->getRepository(TelegramSubscription::class)->findOneBy([
            'telegramChatId' => $chatId,
            'entityType' => $target->type,
            'entityId' => $target->id,
        ]);

        if (!$subscription instanceof TelegramSubscription) {
            return false;
        }

        $this->entityManager->remove($subscription);
        $this->entityManager->flush();

        return true;
    }
}
