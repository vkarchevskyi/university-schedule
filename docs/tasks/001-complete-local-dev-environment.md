# Task: Complete Local Development Environment

## Goal

Make the project easy to start locally with all infrastructure needed for the target architecture.

## Context

Read before starting:

- `docs/00-product-brief.md`
- `docs/02-architecture.md`
- `docs/09-dev-setup.md`

The current `rest-api/compose.yaml` defines PostgreSQL. The architecture also needs Redis and RabbitMQ.

## Scope

- Add Redis to the local compose setup.
- Add RabbitMQ to the local compose setup.
- Add or update `.env.example` files for backend, frontend, and schedule service as needed.
- Document exact local startup commands.
- Ensure local service names match expected environment variables.

## Out Of Scope

- Production deployment.
- Full schedule generation implementation.
- Telegram webhook registration.

## Acceptance Criteria

- PostgreSQL, Redis, and RabbitMQ can run locally.
- Symfony can connect to PostgreSQL.
- The docs describe how to start backend, frontend, and schedule service.
- No real secrets are committed.

## Suggested Files Or Areas

- `rest-api/compose.yaml`
- `rest-api/.env.example`
- `frontend/.env.example`
- `services/schedule`
- `docs/09-dev-setup.md`

## Verification

- `docker compose up -d` from `rest-api`.
- Symfony console can boot.
- Frontend dev server can start.

