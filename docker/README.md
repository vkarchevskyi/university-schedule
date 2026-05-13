# Docker Setup

Project-level Docker configuration lives here because the application is composed of multiple services:

- Symfony REST API in `rest-api/`
- Vue frontend in `frontend/`
- Go schedule worker in `services/schedule/`
- PostgreSQL, Redis, and RabbitMQ infrastructure

## Local Infrastructure

Start the shared infrastructure from the repository root:

```bash
docker compose -f docker/compose.yaml up -d
```

Stop it:

```bash
docker compose -f docker/compose.yaml down
```

View status:

```bash
docker compose -f docker/compose.yaml ps
```

## Local Ports

- PostgreSQL: `127.0.0.1:5432`
- Redis: `127.0.0.1:6379`
- RabbitMQ AMQP: `127.0.0.1:5672`
- RabbitMQ management UI: `http://127.0.0.1:15672`

Default local credentials are development-only and can be overridden with environment variables:

- `POSTGRES_DB`
- `POSTGRES_USER`
- `POSTGRES_PASSWORD`
- `POSTGRES_PORT`
- `REDIS_PORT`
- `RABBITMQ_DEFAULT_USER`
- `RABBITMQ_DEFAULT_PASS`
- `RABBITMQ_DEFAULT_VHOST`
- `RABBITMQ_PORT`
- `RABBITMQ_MANAGEMENT_PORT`

The Symfony API publishes schedule generation jobs to `SCHEDULE_GENERATION_QUEUE` and exam schedule generation jobs to `EXAM_SCHEDULE_GENERATION_QUEUE` through `RABBITMQ_URL`. The Go schedule service consumes both queues when `DATABASE_URL` and `RABBITMQ_URL` are configured.

## Service Apps

For now, run application processes directly while infrastructure runs in Docker:

```bash
cd rest-api
composer install
php bin/console doctrine:migrations:migrate
symfony server:start
```

```bash
cd frontend
pnpm install
pnpm dev
```

```bash
cd services/schedule
go run .
```

Production/VPS Docker Compose can later add containers for the API, frontend, and Go worker.
