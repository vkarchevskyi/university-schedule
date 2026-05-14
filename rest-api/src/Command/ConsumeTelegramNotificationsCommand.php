<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Telegram\SendTelegramNotificationService;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:telegram:consume-notifications')]
final class ConsumeTelegramNotificationsCommand extends Command
{
    public function __construct(
        private readonly string $rabbitmqUrl,
        private readonly string $queueName,
        private readonly SendTelegramNotificationService $notifications,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $parts = parse_url($this->rabbitmqUrl);
        if (!is_array($parts) || !isset($parts['host'])) {
            $output->writeln('<error>RabbitMQ URL is not configured.</error>');

            return Command::FAILURE;
        }

        $connection = new AMQPStreamConnection(
            $parts['host'],
            (int) ($parts['port'] ?? 5672),
            rawurldecode((string) ($parts['user'] ?? 'guest')),
            rawurldecode((string) ($parts['pass'] ?? 'guest')),
            $this->vhost($parts),
        );
        $channel = $connection->channel();
        $channel->queue_declare($this->queueName, false, true, false, false);
        $channel->basic_consume($this->queueName, '', false, false, false, false, function (AMQPMessage $message): void {
            $payload = json_decode($message->body, true);
            $messagePayload = $this->messagePayload($payload);
            if ($messagePayload !== null) {
                $this->notifications->handle($messagePayload);
            }
            $message->ack();
        });

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();

        return Command::SUCCESS;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function messagePayload(mixed $payload): ?array
    {
        if (!is_array($payload)) {
            return null;
        }

        $result = [];
        foreach ($payload as $key => $value) {
            if (!is_string($key)) {
                return null;
            }
            $result[$key] = $value;
        }

        return $result;
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
