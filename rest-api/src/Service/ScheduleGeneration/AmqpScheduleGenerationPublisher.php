<?php

declare(strict_types=1);

namespace App\Service\ScheduleGeneration;

use App\Exception\ApiException;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

final readonly class AmqpScheduleGenerationPublisher implements ScheduleGenerationPublisherInterface
{
    public function __construct(
        private string $rabbitmqUrl,
        private string $queueName,
    ) {}

    public function publish(array $message): void
    {
        $parts = parse_url($this->rabbitmqUrl);

        if (!is_array($parts) || !isset($parts['host'])) {
            throw ApiException::http(['error' => 'RabbitMQ URL is not configured.'], 500);
        }

        $connection = new AMQPStreamConnection(
            $parts['host'],
            (int) ($parts['port'] ?? 5672),
            rawurldecode((string) ($parts['user'] ?? 'guest')),
            rawurldecode((string) ($parts['pass'] ?? 'guest')),
            $this->vhost($parts),
        );

        try {
            $channel = $connection->channel();
            $channel->queue_declare($this->queueName, false, true, false, false);

            $body = json_encode($message, JSON_THROW_ON_ERROR);
            $channel->basic_publish(new AMQPMessage($body, [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]), '', $this->queueName);
            $channel->close();
        } finally {
            $connection->close();
        }
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
