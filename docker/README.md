# Docker Setup

Project-level Docker configuration lives here because the application is composed of multiple services:

- Caddy public entrypoint and Vue static frontend.
- Symfony REST API on FrankenPHP.
- Go schedule validation and generation service.
- PostgreSQL, Redis, and RabbitMQ infrastructure.

## Environment

Create a local Docker env file from the repository root:

```bash
cp docker/.env.example docker/.env
```

Use it by passing `--env-file docker/.env` to Compose commands. Do not commit real secrets.

Important production values:

- `SERVER_NAME`: public domain for Caddy HTTPS, for example `schedule.example.com`. Use `:80` for local HTTP.
- `APP_SECRET`
- `JWT_PASSPHRASE`
- `POSTGRES_PASSWORD`
- `RABBITMQ_DEFAULT_PASS`
- `TELEGRAM_BOT_TOKEN`
- `TELEGRAM_WEBHOOK_SECRET`
- `GEMINI_API_KEY`

## Production-Style Stack

Build and start the full stack:

```bash
docker compose --env-file docker/.env -f docker/compose.yaml up -d --build
```

Stop it:

```bash
docker compose --env-file docker/.env -f docker/compose.yaml down
```

View status:

```bash
docker compose --env-file docker/.env -f docker/compose.yaml ps
```

The production-style stack starts:

- `web`: Caddy, public HTTP/HTTPS, Vue SPA, `/api/*` proxy.
- `rest-api`: Symfony API.
- `rest-api-migrate`: one-shot Doctrine migrations.
- `schedule`: Go validation endpoint and generation workers.
- `database`: PostgreSQL.
- `redis`: Redis.
- `rabbitmq`: RabbitMQ with management UI.

## Local Development Stack

Start the stack with development overrides:

```bash
docker compose --env-file docker/.env -f docker/compose.yaml -f docker/compose.dev.yaml up -d --build
```

Development services:

- Frontend Vite dev server: `http://localhost:5173`
- Symfony API: `http://localhost:8000`
- Go schedule service: `http://localhost:8081`
- PostgreSQL: `127.0.0.1:5432`
- Redis: `127.0.0.1:6379`
- RabbitMQ AMQP: `127.0.0.1:5672`
- RabbitMQ management UI: `http://127.0.0.1:15672`

The dev override bind-mounts `frontend/`, `rest-api/`, and `services/schedule/` into their containers and keeps dependency caches in named Docker volumes.

## Migrations And JWT Keys

`rest-api-migrate` runs Doctrine migrations before the API and schedule service start.

The API image generates Lexik JWT keys on container startup with:

```bash
php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction
```

JWT keys are stored in the `jwt_keys` named volume mounted at `/app/config/jwt`.

## Useful Commands

Run Symfony console:

```bash
docker compose --env-file docker/.env -f docker/compose.yaml exec rest-api php bin/console
```

Run Go tests:

```bash
docker compose --env-file docker/.env -f docker/compose.yaml -f docker/compose.dev.yaml run --rm schedule go test ./...
```

Run frontend build:

```bash
docker compose --env-file docker/.env -f docker/compose.yaml -f docker/compose.dev.yaml run --rm frontend-dev pnpm build
```

Validate Compose files:

```bash
docker compose --env-file docker/.env -f docker/compose.yaml config
docker compose --env-file docker/.env -f docker/compose.yaml -f docker/compose.dev.yaml config
```
