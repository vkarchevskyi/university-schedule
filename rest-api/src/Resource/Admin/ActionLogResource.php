<?php

declare(strict_types=1);

namespace App\Resource\Admin;

final readonly class ActionLogResource
{
    /**
     * @param array<string, mixed>|null $beforePayload
     * @param array<string, mixed>|null $afterPayload
     */
    public function __construct(
        public ?int $id,
        public string $action,
        public string $entityType,
        public int $entityId,
        public string $createdAt,
        public ActionLogUserResource $user,
        public ?array $beforePayload,
        public ?array $afterPayload,
    ) {}
}
