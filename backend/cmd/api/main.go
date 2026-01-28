package main

import (
	"context"
	"log"
	"os"
	"os/signal"
	"syscall"
	"time"

	"github.com/vkarchevskyi/university-schedule/backend/internal/config"
	"github.com/vkarchevskyi/university-schedule/backend/internal/database"
	"github.com/vkarchevskyi/university-schedule/backend/internal/server"
	"github.com/vkarchevskyi/university-schedule/backend/pkg/logger"
)

func main() {
	cfg, err := config.Load()
	if err != nil {
		log.Fatalf("failed to load config: %v", err)
	}

	log := logger.New(cfg.Environment)
	log.Info("Starting application", "env", cfg.Environment)

	ctx := context.Background()
	pgPool, err := database.NewPostgres(ctx, cfg.DatabaseURL)
	if err != nil {
		log.Error("failed to connect to postgres", "error", err)
		os.Exit(1)
	}
	defer pgPool.Close()
	log.Info("Connected to Postgres")

	redisClient, err := database.NewRedis(ctx, cfg.RedisURL)
	if err != nil {
		log.Error("failed to connect to redis", "error", err)
		os.Exit(1)
	}
	defer redisClient.Close()
	log.Info("Connected to Redis")

	srv := server.New(cfg, log)
	httpServer := srv.CreateServer()

	go func() {
		if err := srv.Start(httpServer); err != nil {
			log.Error("server error", "error", err)
		}
	}()

	quit := make(chan os.Signal, 1)
	signal.Notify(quit, os.Interrupt, syscall.SIGTERM)

	<-quit
	log.Info("Shutting down server...")

	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()

	if err := httpServer.Shutdown(ctx); err != nil {
		log.Error("Server forced to shutdown", "error", err)
	}

	log.Info("Server exited properly")
}
