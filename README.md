# University Schedule

A web system for creating, validating, publishing, and viewing university class and exam schedules. Administrators manage academic data and build schedules through a table-first editor; students, teachers, and visitors browse published schedules on the web or via Telegram. Deterministic validation and a Go generation service handle scheduling rules; AI assists with natural-language Telegram queries without making authoritative scheduling decisions.

## Stack

| Layer | Technology |
| --- | --- |
| Frontend | Vue SPA (pnpm) |
| API | Symfony REST API (PHP) |
| Schedule service | Go (validation, async generation) |
| Data | PostgreSQL, Redis, RabbitMQ |

## Repository layout

```text
frontend/          Vue SPA
rest-api/          Symfony REST API
services/schedule/ Go schedule generation service
docker/            Docker Compose for local and production stacks
docs/              Product, architecture, and task documentation
```

## Prerequisites

- Docker (recommended for the full stack)
- PHP and Composer (Symfony API)
- Node.js and pnpm (frontend)
- Go (schedule service)

## Quick start (Docker)

From the repository root:

```bash
cp docker/.env.example docker/.env
make up
```

Or without Make:

```bash
docker compose --env-file docker/.env -f docker/compose.yaml -f docker/compose.dev.yaml up -d --build
```

After startup:

| Service | URL |
| --- | --- |
| Frontend | http://localhost:5173 |
| Symfony API | http://localhost:8000 |
| Go schedule service | http://localhost:8081 |
| PostgreSQL | 127.0.0.1:5432 |
| Redis | 127.0.0.1:6379 |
| RabbitMQ management | http://127.0.0.1:15672 |

Useful Make targets: `make up`, `make down`, `make logs`, `make ps`, `make build`.

`docker/.env` holds Compose-level settings (ports, credentials, image versions). Copy service env files only when you need local overrides:

```bash
cp rest-api/.env.example rest-api/.env.local
cp frontend/.env.example frontend/.env
cp services/schedule/.env.example services/schedule/.env
```

Do not commit real secrets or local env override files.

## Running services individually

### Backend (Symfony)

From `rest-api`:

```bash
composer install
php bin/console doctrine:migrations:migrate
symfony server:start
```

### Frontend

From `frontend`:

```bash
cp .env.example .env
pnpm install
pnpm dev
```

Checks: `pnpm lint`, `pnpm test:unit`, `pnpm test:e2e`, `pnpm build`.

### Schedule service

From `services/schedule`:

```bash
cp .env.example .env
go run .
```

The service listens on `:8081` by default. The Symfony API reaches it via `SCHEDULE_SERVICE_URL` (default `http://127.0.0.1:8081`).

## Documentation

Full product and development docs live in [`docs/`](docs/). Start with [`docs/00-product-brief.md`](docs/00-product-brief.md) for scope and architecture, and [`docs/09-dev-setup.md`](docs/09-dev-setup.md) for detailed local setup notes.
