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

Project-level Docker configuration lives in `docker/`. `docker/compose.yaml` defines PostgreSQL, Redis, and RabbitMQ for local development.

## Backend

From `rest-api`:

```bash
composer install
php bin/console doctrine:migrations:migrate
symfony server:start
```

From the repository root, start infrastructure first:

```bash
docker compose -f docker/compose.yaml up -d
```

Useful infrastructure URLs after startup:

- PostgreSQL: `127.0.0.1:5432`
- Redis: `127.0.0.1:6379`
- RabbitMQ AMQP: `127.0.0.1:5672`
- RabbitMQ management UI: `http://127.0.0.1:15672`

Copy `rest-api/.env.example` to `rest-api/.env.local` if needed, but do not commit real secrets.

## Frontend

From `frontend`:

```bash
cp .env.example .env.local
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
cp .env.example .env.local
go run .
```

The service starts an HTTP server on `:8081` by default and exposes schedule validation for the REST API. Override the bind address with `SCHEDULE_SERVICE_ADDRESS`.

The Symfony API calls the service through `SCHEDULE_SERVICE_URL`, which defaults to `http://127.0.0.1:8081` for local development.

The service still needs implementation around RabbitMQ, PostgreSQL access, and generation logic.

## Environment Variables

Expected categories:

- Database connection.
- Redis URL.
- RabbitMQ URL.
- Frontend API base URL.
- Admin JWT secret or JWT key paths.
- Telegram bot token.
- Telegram webhook secret.
- Gemini API key.

Do not commit real secrets.

## Remaining Setup Tasks

- Add application containers for Symfony, frontend, and Go worker to a deployment compose file.
- Add JWT key-generation instructions once the JWT package is installed.
- Add one-command local orchestration if the separate service commands become tedious.
