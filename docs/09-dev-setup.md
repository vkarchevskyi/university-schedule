# Development Setup

## Repository Layout

```text
frontend/          Vue SPA
rest-api/          Symfony REST API
services/schedule/ Go schedule generation service
docs/              Product, architecture, and task docs
docs/db-diagram.uml Database/entity diagram and schema source of truth
```

## Prerequisites

- PHP and Composer for Symfony.
- Node.js and pnpm for the frontend.
- Go for the schedule service.
- Docker for PostgreSQL and other infrastructure services.

## Current Local Services

Project-level Docker configuration lives in `docker/`. It can run the full application stack or a development stack with bind-mounted source code.

## Backend

From `rest-api` without Docker:

```bash
composer install
php bin/console doctrine:migrations:migrate
symfony server:start
```

From the repository root, start the Docker development stack:

```bash
cp docker/.env.example docker/.env
docker compose --env-file docker/.env -f docker/compose.yaml -f docker/compose.dev.yaml up -d --build
```

Useful Docker development URLs after startup:

- Frontend: `http://localhost:5173`
- Symfony API: `http://localhost:8000`
- Go schedule service: `http://localhost:8081`
- PostgreSQL: `127.0.0.1:5432`
- Redis: `127.0.0.1:6379`
- RabbitMQ AMQP: `127.0.0.1:5672`
- RabbitMQ management UI: `http://127.0.0.1:15672`

`docker/.env` owns Compose-level settings such as ports, image versions, database credentials, RabbitMQ credentials, and `FRONTEND_DEV_API_BASE_URL`.

Service-level runtime env lives beside each service. Copy service examples only when you need local overrides:

```bash
cp rest-api/.env.example rest-api/.env.local
cp frontend/.env.example frontend/.env
cp services/schedule/.env.example services/schedule/.env
```

Do not commit real secrets or local service env override files.

## Frontend

From `frontend`:

```bash
cp .env.example .env
pnpm install
pnpm dev
```

Useful checks:

```bash
pnpm lint
pnpm test:unit
pnpm test:e2e
pnpm build
```

## Schedule Service

From `services/schedule`:

```bash
cp .env.example .env
go run .
```

The service starts an HTTP server on `:8081` by default and exposes schedule validation for the REST API. Override the bind address with `SCHEDULE_SERVICE_ADDRESS`.

The Symfony API calls the service through `SCHEDULE_SERVICE_URL`, which defaults to `http://127.0.0.1:8081` for local development.

When `DATABASE_URL` and `RABBITMQ_URL` are configured, the same Go process also consumes `SCHEDULE_GENERATION_QUEUE` and `EXAM_SCHEDULE_GENERATION_QUEUE`. It writes generated draft lesson schedules and draft exam schedules directly to PostgreSQL.

## Environment Variables

Expected categories:

- Database connection.
- Redis URL.
- RabbitMQ URL.
- Frontend API base URL.
- Admin JWT secret or JWT key paths.
- Telegram bot token.
- Telegram webhook secret.
- Telegram notification queue name, default `telegram_notifications`.
- Gemini API key.
- Gemini model name, default `gemini-2.5-flash`.

Do not commit real secrets.

## Remaining Setup Tasks

- Add VPS-specific secret provisioning and backup automation before production use.
