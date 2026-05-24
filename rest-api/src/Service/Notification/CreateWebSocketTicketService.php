<?php

declare(strict_types=1);

namespace App\Service\Notification;

use App\Entity\User;
use App\Resource\Admin\WebSocketTicketResource;
use Symfony\Component\Uid\Uuid;

final readonly class CreateWebSocketTicketService
{
    public function __construct(private string $secret)
    {
    }

    public function create(User $user): WebSocketTicketResource
    {
        $expiresAt = new \DateTimeImmutable('+2 minutes');
        $payload = [
            'sub' => (int) $user->getId(),
            'exp' => $expiresAt->getTimestamp(),
            'nonce' => Uuid::v4()->toRfc4122(),
        ];

        $encodedPayload = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $encodedPayload, $this->secret, true));

        return new WebSocketTicketResource(
            sprintf('%s.%s', $encodedPayload, $signature),
            $expiresAt->format(DATE_ATOM),
        );
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
