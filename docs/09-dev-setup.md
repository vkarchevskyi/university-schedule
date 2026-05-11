# Development Setup

## Repository Layout

```text
frontend/          Vue SPA
rest-api/          Symfony REST API
services/schedule/ Go schedule generation service
docs/              Product, architecture, and task docs
diagram.uml        Database/entity diagram
```

## Prerequisites

- PHP and Composer for Symfony.
- Node.js and pnpm for the frontend.
- Go for the schedule service.
- Docker for PostgreSQL and other infrastructure services.

## Current Local Services

`rest-api/compose.yaml` currently defines PostgreSQL. Redis and RabbitMQ are part of the target architecture but are not yet present in the compose file.

## Backend

From `rest-api`:

```bash
composer install
docker compose up -d
php bin/console doctrine:migrations:migrate
symfony server:start
```

Adjust commands to the local Symfony tooling actually used in the project.

## Frontend

From `frontend`:

```bash
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
go run .
```

The service currently needs implementation around RabbitMQ, PostgreSQL access, and generation logic.

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

## Open Setup Tasks

- Add Redis to local compose setup.
- Add RabbitMQ to local compose setup.
- Add full Docker Compose setup for local and VPS deployment.
- Add documented `.env.example` files.
- Decide how to run all services together in development.
