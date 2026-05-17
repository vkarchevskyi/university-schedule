# Agent Instructions

These instructions apply to the `services/schedule/` Go service. Use them for tasks that modify schedule validation, class schedule generation, exam schedule generation, worker behavior, database access, or the Chi HTTP endpoint.

## Source Of Truth

- Product and architecture docs live in `../../docs/`.
- API contracts live in `../../docs/04-api-contracts.md`.
- The database schema source of truth is `../../docs/db-diagram.uml`.
- Keep behavior aligned with the Symfony REST API payloads and PostgreSQL schema.
- Do not commit local secrets, generated binaries, or the qualification paper text file.

## General Workflow

- Read the relevant task doc in `../../docs/tasks/` before implementing a feature.
- Keep changes scoped to the requested service behavior.
- Prefer existing package structure and local patterns over new architecture.
- Update docs and the database diagram when service behavior depends on new fields, tables, queues, or payload contracts.
- Run the relevant Go verification commands before finishing.

## Architecture

This service owns deterministic schedule logic. It should not delegate authoritative validation or generation decisions to AI.

Current responsibilities:

- Validate class schedules through the Chi HTTP endpoint.
- Load validation data from PostgreSQL when a schedule id is provided.
- Consume class schedule generation jobs from RabbitMQ.
- Generate class schedule drafts using deterministic rules.
- Consume exam schedule generation jobs from RabbitMQ.
- Generate exam schedule drafts using deterministic rules.
- Persist generated entries directly to PostgreSQL.

Layer responsibilities:

- `main.go`: process wiring, environment configuration, HTTP routes, worker startup, and graceful top-level logging.
- `internal/validation`: schedule validation models, conflict rules, and PostgreSQL loading for validation payloads.
- `internal/generation`: class schedule generation algorithm, generation job worker, and PostgreSQL writes.
- `internal/examgeneration`: exam schedule generation algorithm, exam job worker, and PostgreSQL writes.
- Store types: database access only. Keep scheduling decisions out of SQL/store code unless the query itself is the decision boundary.
- Worker types: queue IO, job lifecycle, store coordination, and error logging. Keep algorithm details inside generators/validators.
- Generator/validator types: pure scheduling rules where practical. Keep them testable without PostgreSQL or RabbitMQ.

## Go Practices

- Keep packages small and purpose-driven.
- Prefer simple structs and functions over broad abstractions.
- Use interfaces only at package boundaries that need tests or swappable implementations.
- Pass `context.Context` to database, queue, and long-running operations.
- Return errors with useful context using `fmt.Errorf("operation: %w", err)`.
- Do not panic for expected runtime failures.
- Keep exported names documented when they form a package boundary.
- Use table-driven tests for validation and generation rule variants.
- Keep time handling explicit. Avoid implicit local timezone assumptions for schedule-critical logic.
- Use `gofmt`/`go test` output as the formatting and correctness baseline.

## HTTP Rules

- Use Chi for HTTP routes when an HTTP server is required.
- Keep HTTP handlers thin: decode request, validate transport shape, call service logic, encode response.
- Keep route response formats aligned with `../../docs/04-api-contracts.md`.
- Return clear status codes:
  - `400` for malformed request bodies.
  - `503` when a schedule id requires unavailable database access.
  - `500` only for unexpected server failures.
- Do not expose internal worker or database errors directly in HTTP responses.

## Validation Rules

- Validation must be deterministic and local to this Go service.
- Validation results should use stable conflict `Type` values because the REST API and frontend may depend on them.
- Add tests for every new conflict type or rule branch.
- Prefer validating complete schedules in memory after loading the required data from PostgreSQL.
- Do not let generation bypass validation rules. Generated drafts should be valid or report diagnostics explaining why not.

## Generation Rules

- Class schedule generation should keep CSP as the primary construction approach and Tabu search as the optimization step.
- Validation-only flows should use CSP/rule checks without optimization.
- Exam generation must be part of the first shipped version and should remain deterministic.
- Persist generated entries directly to PostgreSQL through store methods.
- Keep generation diagnostics explicit enough for the REST API to expose useful job status.
- Do not publish generated schedules automatically. Generated schedules remain drafts for review.

## Database And Queue Rules

- Use PostgreSQL through `pgx`.
- Keep SQL statements explicit and close to the store that owns them.
- Use transactions for multi-step writes that must succeed or fail together.
- Make queue names configurable through environment variables and preserve documented defaults.
- Workers should log failures with context and keep the process alive when a single job fails.
- Close database and queue resources when ownership is clear.

## Configuration

Environment variables used by this service include:

- `SCHEDULE_SERVICE_ADDRESS`, default `:8081`
- `DATABASE_URL`
- `RABBITMQ_URL`
- `SCHEDULE_GENERATION_QUEUE`, default `schedule_generation`
- `EXAM_SCHEDULE_GENERATION_QUEUE`, default `exam_schedule_generation`
- `EXAM_CONSULTATION_DAYS_BEFORE`, default `1`
- `EXAM_MIN_DAYS_BETWEEN_GROUP_EXAMS`, default `1`

Document new environment variables in `../../docs/09-dev-setup.md` and deployment docs when adding them.

## Tests

Use focused tests at the level where behavior matters:

- Unit tests for pure validation rules and generator decisions.
- Store tests only when SQL/query behavior is non-trivial and a database test setup is available.
- Worker tests for job state transitions, malformed payload behavior, and store/generator coordination when practical.
- HTTP tests for endpoint contract changes.

Testing rules:

- Add or update tests for every validation rule, generation rule, or job lifecycle change.
- Use table-driven tests for conflict permutations and schedule generation constraints.
- Keep fixtures small and explicit.
- Prefer deterministic test data over randomness.
- If randomness is needed for optimization, inject or seed it so tests are repeatable.
- Test failure paths, not only successful schedules.

## Go Verification

From `services/schedule/`:

```bash
gofmt -w .
go test ./...
go vet ./...
go test -race ./...
```

Run `go test ./...` for all changes. Run `go test -race ./...` before finishing worker, concurrency, or queue-related changes. If a command cannot be run in the current environment, state that explicitly.

## Change Discipline

- Keep diffs surgical and directly tied to the requested task.
- Do not refactor unrelated validation, generation, SQL, or worker code while implementing a feature.
- Remove imports, variables, helper functions, fixtures, and tests made unused by your own changes.
- Match existing naming and package layout.
- Prefer clear deterministic code over speculative configurability.
