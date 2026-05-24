package examgeneration

import (
	"context"
	"encoding/json"
	"fmt"
	"log"
	"time"

	amqp "github.com/rabbitmq/amqp091-go"
	"github.com/vkarchevskyi/university-schedule/services/schedule/internal/notifications"
)

type Worker struct {
	store     *PostgresStore
	generator Generator
	queueName string
	publisher eventPublisher
}

type eventPublisher interface {
	Publish(context.Context, notifications.Event) error
}

func NewWorker(store *PostgresStore, generator Generator, queueName string, publisher eventPublisher) Worker {
	return Worker{store: store, generator: generator, queueName: queueName, publisher: publisher}
}

func (worker Worker) Start(ctx context.Context, rabbitmqURL string) error {
	connection, err := amqp.Dial(rabbitmqURL)
	if err != nil {
		return fmt.Errorf("connect rabbitmq: %w", err)
	}
	defer connection.Close()

	channel, err := connection.Channel()
	if err != nil {
		return fmt.Errorf("open channel: %w", err)
	}
	defer channel.Close()

	queue, err := channel.QueueDeclare(worker.queueName, true, false, false, false, nil)
	if err != nil {
		return fmt.Errorf("declare queue: %w", err)
	}

	deliveries, err := channel.Consume(queue.Name, "", false, false, false, false, nil)
	if err != nil {
		return fmt.Errorf("consume queue: %w", err)
	}

	log.Printf("Exam schedule generation worker consuming queue %s", queue.Name)
	for {
		select {
		case <-ctx.Done():
			return ctx.Err()
		case delivery, ok := <-deliveries:
			if !ok {
				return fmt.Errorf("exam generation queue deliveries closed")
			}
			if err := worker.handleDelivery(ctx, delivery); err != nil {
				log.Printf("exam generation job failed: %v", err)
				_ = delivery.Nack(false, false)
				continue
			}
			_ = delivery.Ack(false)
		}
	}
}

func (worker Worker) handleDelivery(parent context.Context, delivery amqp.Delivery) error {
	ctx, cancel := context.WithTimeout(parent, 2*time.Minute)
	defer cancel()

	var message JobMessage
	if err := json.Unmarshal(delivery.Body, &message); err != nil {
		return fmt.Errorf("decode message: %w", err)
	}

	if err := worker.Process(ctx, message); err != nil {
		_ = worker.store.MarkFailed(ctx, message.JobID, err.Error())
		worker.publishJob(ctx, message.JobID)
		return err
	}

	return nil
}

func (worker Worker) Process(ctx context.Context, message JobMessage) error {
	if err := worker.store.MarkRunning(ctx, message.JobID); err != nil {
		return fmt.Errorf("mark job running: %w", err)
	}
	worker.publishJob(ctx, message.JobID)

	input, err := worker.store.LoadInput(ctx, message.SemesterID)
	if err != nil {
		return err
	}

	entries, score, status, err := worker.generator.Generate(input)
	if err != nil {
		return err
	}

	result := Result{
		QualityScore:  score,
		QualityStatus: status,
		Diagnostics: map[string]any{
			"generatedEntryCount": len(entries),
			"minimumQualityScore": minimumQualityScore,
		},
	}

	_, err = worker.store.CompleteJobWithDraftExamSchedule(ctx, message, entries, result)
	if err != nil {
		return err
	}
	worker.publishJob(ctx, message.JobID)

	return nil
}

func (worker Worker) publishJob(ctx context.Context, jobID string) {
	if worker.publisher == nil {
		return
	}

	job, err := worker.store.LoadJob(ctx, jobID)
	if err != nil {
		log.Printf("load exam generation notification job: %v", err)
		return
	}

	if err := worker.publisher.Publish(ctx, notifications.Event{
		Type:   "exam_schedule_generation_job",
		JobID:  job.ID,
		Status: job.Status,
		Job:    job,
	}); err != nil {
		log.Printf("publish exam generation notification: %v", err)
	}
}
