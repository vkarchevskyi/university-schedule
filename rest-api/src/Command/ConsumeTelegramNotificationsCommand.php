<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\ApiException;
use App\Service\Amqp\AmqpConnectionFactory;
use App\Service\Telegram\SendTelegramNotificationService;
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
        private readonly AmqpConnectionFactory $connections,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $connection = $this->connections->create($this->rabbitmqUrl);
        } catch (ApiException) {
            $output->writeln('<error>RabbitMQ URL is not configured.</error>');

            return Command::FAILURE;
        }

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
}
