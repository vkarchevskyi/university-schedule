# Task: Schedule Generation Service

## Goal

Connect the Symfony API to the Go schedule generation service through RabbitMQ and produce reviewable generated draft schedules.

## Context

Read before starting:

- `docs/02-architecture.md`
- `docs/06-scheduling-engine.md`
- `docs/04-api-contracts.md`

Generation is asynchronous because it can be computationally expensive.

## Scope

- Add generation job endpoint in Symfony.
- Publish generation requests to RabbitMQ.
- Implement RabbitMQ consumer in Go.
- Load required data and teaching-load requirements for the selected semester.
- Produce a candidate schedule using CSP for feasible construction and Tabu search for optimization.
- Write generated entries directly to PostgreSQL as a draft or generated schedule.
- Return generation status/result metadata to Symfony.

## Out Of Scope

- Perfect optimization.
- AI-generated schedules.
- Complex UI beyond viewing status/result.

## Acceptance Criteria

- Admin can start generation for a semester.
- Job status changes from queued to running to complete or failed.
- Generated result is not automatically published.
- Generated draft passes hard validation or reports why it cannot.
- Generated draft satisfies teaching-load requirements or reports unsatisfied requirements.
- Generated draft has no hard conflicts and should target at least 80/100 soft score.
- Failures are visible and do not affect current published schedules.

## Suggested Files Or Areas

- `rest-api/src`
- `services/schedule/main.go`
- `services/schedule`
- `rest-api/compose.yaml`
