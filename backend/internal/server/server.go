package server

import (
	"fmt"
	"log/slog"
	"net/http"
	"time"

	"github.com/go-chi/chi/v5"
	"github.com/go-chi/chi/v5/middleware"
	"github.com/jackc/pgx/v5/pgxpool"
	"github.com/redis/go-redis/v9"
	"github.com/vkarchevskyi/university-schedule/backend/internal/config"
	"github.com/vkarchevskyi/university-schedule/backend/internal/handler"
)

type Server struct {
	router      *chi.Mux
	logger      *slog.Logger
	config      *config.Config
	pgPool      *pgxpool.Pool
	redisClient *redis.Client
}

func New(cfg *config.Config, logger *slog.Logger) *Server {
	s := &Server{
		router: chi.NewRouter(),
		logger: logger,
		config: cfg,
	}

	s.setupMiddleware()
	s.setupRoutes()

	return s
}

func (s *Server) setupMiddleware() {
	s.router.Use(middleware.RequestID)
	s.router.Use(middleware.RealIP)
	s.router.Use(middleware.Logger)
	s.router.Use(middleware.Recoverer)
	s.router.Use(middleware.Timeout(60 * time.Second))
}

func (s *Server) setupRoutes() {
	s.router.Get("/health", func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)
		w.Write([]byte("OK"))
	})

	s.router.Get("/hello", handler.Hello)

	// Mount other routes here
	// s.router.Mount("/api/v1", v1Router)
}

func (s *Server) CreateServer() *http.Server {
	return &http.Server{
		Addr:         fmt.Sprintf(":%s", s.config.Port),
		Handler:      s.router,
		IdleTimeout:  time.Minute,
		ReadTimeout:  10 * time.Second,
		WriteTimeout: 30 * time.Second,
	}
}

// Start runs the server (call this from main)
func (s *Server) Start(srv *http.Server) error {
	s.logger.Info("Starting server on port " + s.config.Port)
	if err := srv.ListenAndServe(); err != nil && err != http.ErrServerClosed {
		return fmt.Errorf("server failed: %w", err)
	}
	return nil
}
