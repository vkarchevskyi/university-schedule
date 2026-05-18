<?php

declare(strict_types=1);

namespace App\Service\Amqp;

use App\Exception\ApiException;
use PhpAmqpLib\Connection\AMQPStreamConnection;

final readonly class AmqpConnectionFactory
{
    public function create(string $rabbitmqUrl): AMQPStreamConnection
    {
        $parts = parse_url($rabbitmqUrl);

        if (!is_array($parts) || !isset($parts['host'])) {
            throw ApiException::http(['error' => 'RabbitMQ URL is not configured.'], 500);
        }

        return new AMQPStreamConnection(
            $parts['host'],
            (int) ($parts['port'] ?? 5672),
            rawurldecode((string) ($parts['user'] ?? 'guest')),
            rawurldecode((string) ($parts['pass'] ?? 'guest')),
            $this->vhost($parts),
        );
    }

    /** @param array<string, mixed> $parts */
    private function vhost(array $parts): string
    {
        $rawPath = $parts['path'] ?? '/';
        $path = is_string($rawPath) ? $rawPath : '/';
        $vhost = ltrim($path, '/');

        return $vhost === '' ? '/' : rawurldecode($vhost);
    }
}
