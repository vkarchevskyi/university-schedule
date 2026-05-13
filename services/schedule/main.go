package main

import (
	"context"
	"encoding/json"
	"log"
	"net/http"
	"os"
	"time"

	"github.com/go-chi/chi/v5"
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

	router.Get("/health", func(writer http.ResponseWriter, request *http.Request) {
		writer.WriteHeader(http.StatusNoContent)
	})

	router.Post("/validate-schedule", func(writer http.ResponseWriter, request *http.Request) {
		var payload validation.ScheduleValidationRequest
		if err := json.NewDecoder(request.Body).Decode(&payload); err != nil {
			http.Error(writer, "invalid validation request", http.StatusBadRequest)
			return
		}

		schedule, ok := requestSchedule(request, payload, store)
		if !ok {
			writer.WriteHeader(http.StatusServiceUnavailable)
			return
		}

		writer.Header().Set("Content-Type", "application/json")
		if err := json.NewEncoder(writer).Encode(validator.Validate(schedule)); err != nil {
			log.Printf("encode validation response: %v", err)
		}
	})

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

func requestSchedule(request *http.Request, payload validation.ScheduleValidationRequest, store *validation.PostgresStore) (validation.Schedule, bool) {
	if payload.Schedule != nil {
		return *payload.Schedule, true
	}

	if payload.ScheduleID == 0 || store == nil {
		return validation.Schedule{}, false
	}

	ctx, cancel := context.WithTimeout(request.Context(), 5*time.Second)
	defer cancel()

	schedule, err := store.LoadSchedule(ctx, payload.ScheduleID)
	if err != nil {
		log.Printf("load schedule %d: %v", payload.ScheduleID, err)
		return validation.Schedule{}, false
	}

	return schedule, true
}
