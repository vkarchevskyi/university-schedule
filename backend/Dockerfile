# Build Stage
FROM golang:1.25-alpine AS builder

WORKDIR /app

COPY go.mod go.sum ./
RUN go mod download

COPY . .

RUN CGO_ENABLED=0 GOOS=linux go build -o /api cmd/api/main.go

# Run Stage
FROM alpine:latest AS runner

WORKDIR /

COPY --from=builder /api /api
# Copy migrations if running locally in container, though usually done via separate job
COPY --from=builder /app/migrations /migrations
# Copy .env if needed, but prefer env vars from docker-compose

EXPOSE 8080

CMD ["/api"]
