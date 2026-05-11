# Task: Deploy Demo Release

## Goal

Deploy a working demo suitable for qualification defense or user testing.

## Context

Read before starting:

- `docs/10-deployment.md`
- `docs/00-product-brief.md`

The demo should prove the product loop: manage data, publish schedule, view schedule, and use Telegram if configured.

## Scope

- Prepare full Docker Compose deployment for a VPS.
- Configure environment variables and secrets.
- Run migrations.
- Create initial admin user.
- Seed or import demo academic data.
- Build and deploy frontend.
- Deploy Symfony API.
- Deploy Redis, RabbitMQ, PostgreSQL, and the Go worker.
- Configure HTTPS.
- Register Telegram webhook.
- Verify public and admin flows.

## Out Of Scope

- Complex autoscaling.
- Multi-tenant hosting.
- Full monitoring stack beyond basic logs.

## Acceptance Criteria

- Public schedule URL works.
- Admin can log in.
- Admin can publish a valid schedule.
- Invalid schedule publication is blocked.
- Automatic schedule generation works through the Go worker.
- Exam scheduling is available.
- Telegram schedule lookup works.
- HTTPS is enabled.
- Deployment steps are documented.

## Suggested Files Or Areas

- `docs/10-deployment.md`
- deployment config files chosen during implementation
- environment examples
