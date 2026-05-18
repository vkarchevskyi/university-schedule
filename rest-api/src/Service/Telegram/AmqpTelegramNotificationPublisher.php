<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Service\Amqp\AmqpConnectionFactory;
use PhpAmqpLib\Message\AMQPMessage;

final readonly class AmqpTelegramNotificationPublisher implements TelegramNotificationPublisherInterface
{
    public function __construct(
        private string $rabbitmqUrl,
        private string $queueName,
        private AmqpConnectionFactory $connections,
    ) {}

    public function publish(array $message): void
    {
        $connection = $this->connections->create($this->rabbitmqUrl);

        try {
            $channel = $connection->channel();
            $channel->queue_declare($this->queueName, false, true, false, false);
            $channel->basic_publish(new AMQPMessage(json_encode($message, JSON_THROW_ON_ERROR), [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]), '', $this->queueName);
            $channel->close();
        } finally {
            $connection->close();
        }
    }
}
