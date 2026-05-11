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

## Monitoring

Minimum useful signals:

- API errors.
- Telegram webhook failures.
- LLM parsing failures.
- Generation job failures.
- Database migration status.
- Queue depth for generation jobs and notifications.

## Backup

PostgreSQL should be backed up before production use. At minimum, document how to export and restore the database for demo and defense scenarios.

## Decisions

- Deployment target is a VPS.
- Use a full Docker Compose setup for the deployed release.
- HTTPS and Telegram webhook hosting are required.
- The Go worker is enabled in the first deployed version.
