<?php

declare(strict_types=1);

namespace App\Resource\Admin;

final readonly class WebSocketTicketResource
{
    public function __construct(
        public string $ticket,
        public string $expiresAt,
    ) {
    }
}
