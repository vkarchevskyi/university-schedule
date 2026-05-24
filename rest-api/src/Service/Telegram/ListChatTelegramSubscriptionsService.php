<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\TelegramSubscription;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ListChatTelegramSubscriptionsService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ResolveTelegramTargetService $targets,
    ) {}

    /** @return list<TelegramTargetListItem> */
    public function list(int $chatId): array
    {
        $subscriptions = $this->entityManager->getRepository(TelegramSubscription::class)->findBy(
            ['telegramChatId' => $chatId],
            ['entityType' => 'ASC', 'entityId' => 'ASC'],
        );

        $items = [];
        foreach ($subscriptions as $subscription) {
            $target = $this->targets->getById($subscription->getEntityType(), $subscription->getEntityId());
            if ($target !== null) {
                $items[] = new TelegramTargetListItem($target->type, $target->id, $target->label);
            }
        }

        return $items;
    }
}
