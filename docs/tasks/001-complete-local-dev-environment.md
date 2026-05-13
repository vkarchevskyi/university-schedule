# Task: Complete Local Development Environment

## Goal

Make the project easy to start locally with all infrastructure needed for the target architecture.

## Context

Read before starting:

- `docs/00-product-brief.md`
- `docs/02-architecture.md`
- `docs/09-dev-setup.md`

Docker configuration belongs in the root `docker/` folder because the project has multiple services. Local infrastructure should be started from `docker/compose.yaml`.

## Scope

- Add Redis to the local compose setup.
- Add RabbitMQ to the local compose setup.
- Keep project-level Docker configuration under `docker/`, not inside a single service folder.
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

- `docker/compose.yaml`
- `docker/README.md`
- `rest-api/.env.example`
- `frontend/.env.example`
- `services/schedule`
- `docs/09-dev-setup.md`

## Verification

- `docker compose -f docker/compose.yaml up -d` from the repository root.
- Symfony console can boot.
- Frontend dev server can start.
