# Deployment

## Deployment Goal

Ship the system as isolated components that can be restarted independently:

- Frontend static app.
- Symfony API.
- PostgreSQL.
- Redis.
- RabbitMQ.
- Go schedule worker.

The first release targets a VPS with a full Docker Compose setup.

## Environments

Recommended environments:

- Local development.
- Demo/staging.
- Production.

The qualification/demo release can use a simpler deployment if it still proves the full flow.

## Configuration

All environment-specific values should come from environment variables or deployment secrets:

- Database credentials.
- Redis URL.
- RabbitMQ URL.
- API URL.
- Telegram bot token.
- Telegram webhook secret.
- LLM API key.
- Auth secrets.

The Docker deployment uses `docker/.env` for Compose-level orchestration values. Start from `docker/.env.example`, copy it to a deployment-only env file, and replace infrastructure and deployment placeholders before starting the stack.

Service runtime secrets stay in service-local env files:

- `rest-api/.env.local` for Symfony secrets, Telegram credentials, Gemini credentials, and CORS origins.
- `services/schedule/.env` for worker-specific queue names and generation tuning.
- `frontend/.env` only when building or running the frontend outside the production Docker image.

## Release Checklist

- Migrations applied.
- Admin user created securely.
- Frontend points to deployed API.
- Telegram webhook registered.
- Redis reachable.
- RabbitMQ reachable if generation is enabled.
- RabbitMQ reachable.
- Schedule worker running.
- HTTPS configured for the frontend/API.
- Telegram webhook registered against the HTTPS API URL.
- Logs are available for API and worker.
- Public schedule route works without auth.
- Admin routes reject anonymous users.
- Invalid schedules cannot be published.
- Secrets are not committed.

## Docker Compose Deployment

Build and start the full VPS-ready stack from the repository root:

```bash
docker compose --env-file docker/.env -f docker/compose.yaml up -d --build
```

The stack includes:

- Caddy public entrypoint with automatic HTTPS when `SERVER_NAME` is a real domain.
- Vue frontend served as static files.
- Symfony REST API on FrankenPHP.
- One-shot Doctrine migration service.
- Go schedule validation and generation service.
- PostgreSQL, Redis, and RabbitMQ.

Set `SERVER_NAME` to the public HTTPS domain used for the frontend, API, Telegram webhook, and WebSocket generation notifications. Caddy routes `/api/admin/notifications/ws` to the Go schedule service, routes the remaining `/api/*` paths to Symfony, and serves the SPA for all other paths.

The API generates JWT keys on startup into a named Docker volume and skips generation when keys already exist.

## Monitoring

Minimum useful signals:

- API errors.
- Telegram webhook failures.
- LLM parsing failures.
- Generation job failures.
- Database migration status.
- Queue depth for generation jobs, generation WebSocket notifications, and Telegram notifications.

## Backup

PostgreSQL should be backed up before production use. At minimum, document how to export and restore the database for demo and defense scenarios.

## Decisions

- Deployment target is a VPS.
- Use a full Docker Compose setup for the deployed release.
- HTTPS and Telegram webhook hosting are required.
- The Go worker is enabled in the first deployed version.
