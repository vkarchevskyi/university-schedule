<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use Psr\Cache\CacheItemPoolInterface;

final readonly class TelegramFlowStateService
{
    private const TTL_SECONDS = 600;

    public function __construct(private CacheItemPoolInterface $cache) {}

    public function save(int $chatId, TelegramFlowState $state): void
    {
        $item = $this->cache->getItem($this->key($chatId));
        $item->expiresAfter(self::TTL_SECONDS);
        $item->set([
            'action' => $state->action,
            'targetType' => $state->targetType,
            'page' => $state->page,
        ]);

        $this->cache->save($item);
    }

    public function get(int $chatId): ?TelegramFlowState
    {
        $item = $this->cache->getItem($this->key($chatId));
        if (!$item->isHit()) {
            return null;
        }

        $value = $item->get();
        if (!is_array($value) || !is_string($value['action'] ?? null) || !is_int($value['page'] ?? null)) {
            return null;
        }

        $targetType = $value['targetType'] ?? null;
        if ($targetType !== null && !is_string($targetType)) {
            return null;
        }

        return new TelegramFlowState($value['action'], $targetType, $value['page']);
    }

    public function clear(int $chatId): void
    {
        $this->cache->deleteItem($this->key($chatId));
    }

    private function key(int $chatId): string
    {
        return sprintf('telegram_flow_%d', $chatId);
    }
}
