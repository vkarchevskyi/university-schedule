package notifications

import (
	"context"
	"encoding/json"
	"fmt"
	"sync"

	amqp "github.com/rabbitmq/amqp091-go"
)

const DefaultQueueName = "generation_notifications"

type Event struct {
	Type   string `json:"type"`
	JobID  string `json:"jobId"`
	Status string `json:"status"`
	Job    any    `json:"job"`
}

type Publisher struct {
	connection *amqp.Connection
	channel    *amqp.Channel
	queueName  string
	mu         sync.Mutex
}

func NewPublisher(rabbitmqURL string, queueName string) (*Publisher, error) {
	if queueName == "" {
		queueName = DefaultQueueName
	}

	connection, err := amqp.Dial(rabbitmqURL)
	if err != nil {
		return nil, fmt.Errorf("connect rabbitmq notifications: %w", err)
	}

	channel, err := connection.Channel()
	if err != nil {
		_ = connection.Close()
		return nil, fmt.Errorf("open notification channel: %w", err)
	}

	if _, err := channel.QueueDeclare(queueName, false, false, false, false, nil); err != nil {
		_ = channel.Close()
		_ = connection.Close()
		return nil, fmt.Errorf("declare notification queue: %w", err)
	}

	return &Publisher{connection: connection, channel: channel, queueName: queueName}, nil
}

func (publisher *Publisher) Publish(ctx context.Context, event Event) error {
	body, err := json.Marshal(event)
	if err != nil {
		return fmt.Errorf("marshal notification event: %w", err)
	}

	publisher.mu.Lock()
	defer publisher.mu.Unlock()

	return publisher.channel.PublishWithContext(ctx, "", publisher.queueName, false, false, amqp.Publishing{
		ContentType:  "application/json",
		DeliveryMode: amqp.Transient,
		Body:         body,
	})
}

func (publisher *Publisher) Close() error {
	if err := publisher.channel.Close(); err != nil {
		_ = publisher.connection.Close()
		return err
	}

	return publisher.connection.Close()
}
