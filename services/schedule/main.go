package main

import (
	"context"
	"database/sql"
	"encoding/json"
	"errors"
	"log"
	"net/http"
	"os"
	"strconv"
	"time"

	"github.com/go-chi/chi/v5"
	"github.com/vkarchevskyi/university-schedule/services/schedule/internal/examgeneration"
	"github.com/vkarchevskyi/university-schedule/services/schedule/internal/generation"
	"github.com/vkarchevskyi/university-schedule/services/schedule/internal/notifications"
	"github.com/vkarchevskyi/university-schedule/services/schedule/internal/validation"
)

func main() {
	router := chi.NewRouter()
	validator := validation.NewValidator()
	store, err := validationStore()
	if err != nil {
		log.Printf("schedule validation database access disabled: %v", err)
	}
	if store != nil {
		defer func() {
			if err := store.Close(); err != nil {
				log.Printf("close database: %v", err)
			}
		}()
	}
	notificationPublisher := notificationPublisher()
	if notificationPublisher != nil {
		defer func() {
			if err := notificationPublisher.Close(); err != nil {
				log.Printf("close notification publisher: %v", err)
			}
		}()
	}
	startGenerationWorker(validator, notificationPublisher)
	startExamGenerationWorker(notificationPublisher)
	startNotificationBroker(router)

	router.Get("/health", func(writer http.ResponseWriter, request *http.Request) {
		writer.WriteHeader(http.StatusNoContent)
	})

	router.Post("/validate-schedule", validateScheduleHandler(validator, store))

	address := os.Getenv("SCHEDULE_SERVICE_ADDRESS")
	if address == "" {
		address = ":8081"
	}

	log.Printf("Starting schedule service on %s", address)
	log.Fatal(http.ListenAndServe(address, router))
}

func validationStore() (*validation.PostgresStore, error) {
	databaseURL := os.Getenv("DATABASE_URL")
	if databaseURL == "" {
		return nil, nil
	}

	return validation.NewPostgresStore(databaseURL)
}

func notificationPublisher() *notifications.Publisher {
	rabbitmqURL := os.Getenv("RABBITMQ_URL")
	if rabbitmqURL == "" {
		return nil
	}

	publisher, err := notifications.NewPublisher(rabbitmqURL, notificationQueueName())
	if err != nil {
		log.Printf("web notification publisher disabled: %v", err)
		return nil
	}

	return publisher
}

func startGenerationWorker(validator validation.Validator, publisher *notifications.Publisher) {
	databaseURL := os.Getenv("DATABASE_URL")
	rabbitmqURL := os.Getenv("RABBITMQ_URL")
	if databaseURL == "" || rabbitmqURL == "" {
		return
	}

	queueName := os.Getenv("SCHEDULE_GENERATION_QUEUE")
	if queueName == "" {
		queueName = "schedule_generation"
	}

	store, err := generation.NewPostgresStore(databaseURL)
	if err != nil {
		log.Printf("schedule generation worker disabled: %v", err)
		return
	}

	worker := generation.NewWorker(store, generation.NewGenerator(validator), queueName, publisher)
	go func() {
		defer func() {
			if err := store.Close(); err != nil {
				log.Printf("close generation database: %v", err)
			}
		}()
		if err := worker.Start(context.Background(), rabbitmqURL); err != nil {
			log.Printf("schedule generation worker stopped: %v", err)
		}
	}()
}

func startExamGenerationWorker(publisher *notifications.Publisher) {
	databaseURL := os.Getenv("DATABASE_URL")
	rabbitmqURL := os.Getenv("RABBITMQ_URL")
	if databaseURL == "" || rabbitmqURL == "" {
		return
	}

	queueName := os.Getenv("EXAM_SCHEDULE_GENERATION_QUEUE")
	if queueName == "" {
		queueName = "exam_schedule_generation"
	}

	store, err := examgeneration.NewPostgresStore(databaseURL, intEnv("EXAM_CONSULTATION_DAYS_BEFORE", 1), intEnv("EXAM_MIN_DAYS_BETWEEN_GROUP_EXAMS", 1))
	if err != nil {
		log.Printf("exam schedule generation worker disabled: %v", err)
		return
	}

	worker := examgeneration.NewWorker(store, examgeneration.NewGenerator(), queueName, publisher)
	go func() {
		defer func() {
			if err := store.Close(); err != nil {
				log.Printf("close exam generation database: %v", err)
			}
		}()
		if err := worker.Start(context.Background(), rabbitmqURL); err != nil {
			log.Printf("exam schedule generation worker stopped: %v", err)
		}
	}()
}

func startNotificationBroker(router chi.Router) {
	rabbitmqURL := os.Getenv("RABBITMQ_URL")
	secret := os.Getenv("WEBSOCKET_TICKET_SECRET")
	if rabbitmqURL == "" || secret == "" {
		return
	}

	broker := notifications.NewBroker(notifications.NewTicketValidator(secret))
	router.Get("/api/admin/notifications/ws", broker.ServeHTTP)

	go func() {
		if err := broker.StartRabbitMQConsumer(context.Background(), rabbitmqURL, notificationQueueName()); err != nil {
			log.Printf("web notification broker stopped: %v", err)
		}
	}()
}

func notificationQueueName() string {
	queueName := os.Getenv("GENERATION_NOTIFICATIONS_QUEUE")
	if queueName == "" {
		return notifications.DefaultQueueName
	}

	return queueName
}

func intEnv(name string, fallback int) int {
	value := os.Getenv(name)
	if value == "" {
		return fallback
	}

	parsed, err := strconv.Atoi(value)
	if err != nil {
		log.Printf("invalid %s value %q, using %d", name, value, fallback)
		return fallback
	}

	return parsed
}

func validateScheduleHandler(validator validation.Validator, store *validation.PostgresStore) http.HandlerFunc {
	return func(writer http.ResponseWriter, request *http.Request) {
		var payload validation.ScheduleValidationRequest
		if err := json.NewDecoder(request.Body).Decode(&payload); err != nil {
			http.Error(writer, "invalid validation request", http.StatusBadRequest)
			return
		}

		schedule, status := requestSchedule(request, payload, store)
		if status != http.StatusOK {
			writer.WriteHeader(status)
			return
		}

		writer.Header().Set("Content-Type", "application/json")
		if err := json.NewEncoder(writer).Encode(validator.Validate(schedule)); err != nil {
			log.Printf("encode validation response: %v", err)
		}
	}
}

func requestSchedule(request *http.Request, payload validation.ScheduleValidationRequest, store *validation.PostgresStore) (validation.Schedule, int) {
	if payload.Schedule != nil {
		return *payload.Schedule, http.StatusOK
	}

	if payload.ScheduleID == 0 {
		return validation.Schedule{}, http.StatusBadRequest
	}

	if store == nil {
		return validation.Schedule{}, http.StatusServiceUnavailable
	}

	ctx, cancel := context.WithTimeout(request.Context(), 5*time.Second)
	defer cancel()

	schedule, err := store.LoadSchedule(ctx, payload.ScheduleID)
	if err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return validation.Schedule{}, http.StatusNotFound
		}
		log.Printf("load schedule %d: %v", payload.ScheduleID, err)
		return validation.Schedule{}, http.StatusServiceUnavailable
	}

	return schedule, http.StatusOK
}
