# Agent Instructions

These instructions apply to the `rest-api/` Symfony REST API. They are written for Codex and other coding agents working on PHP API tasks.

## Source Of Truth

- Product and architecture docs live in `../docs/`.
- The database schema source of truth is `../docs/db-diagram.uml`.
- Do not commit local secrets or the qualification paper text file.

## General Workflow

- Read the relevant task doc in `../docs/tasks/` before implementing a feature.
- Keep changes scoped to the requested feature.
- Prefer existing project patterns over new abstractions.
- Update docs when public API behavior, data model behavior, or setup steps change.
- Run the relevant verification commands before finishing.

## Architecture

Treat the API as an application boundary, not as direct access to Doctrine entities.

Use this flow for write operations:

```text
HTTP request -> Request DTO -> Symfony Validator -> Application service -> Entity/domain model -> Response resource
```

Use this flow for read operations:

```text
HTTP request/query -> Query DTO when validation is needed -> Application service -> Repository/entities -> Response resource
```

Layer responsibilities:

- Controllers: routing, authentication boundary, request DTO mapping, status codes, and delegating to services.
- DTOs: API input shape and transport-level validation.
- Services: application use cases, business rules, transactions, entity mutation, and response composition.
- Repositories: persistence queries only. Do not put business decisions in repositories.
- Entities: persistence model and local invariants. Do not expose entities directly as API request/response contracts.
- Event subscribers: cross-cutting HTTP concerns such as API exception formatting.

Controllers must stay tiny. A controller action should normally call one service method and return its result.

## Service Design

- Services must be single-purpose application use cases.
- Each service should have one public method. Use `handle()` for commands/mutations, `get()` for single-item queries, `list()` for collection queries, or another precise verb when it reads better.
- The service class name must describe the use case, not just the entity. Prefer names such as `CreateTeacherService`, `UpdateTeacherService`, `ListTeachersService`, and `GetPublicScheduleService`.
- Group services by entity or feature under `src/Service/`. For entity services, use subfolders such as `src/Service/Teacher/`, `src/Service/Group/`, and `src/Service/Schedule/`.
- Do not create broad entity services with many CRUD methods. Split operations into dedicated services and inject only the service needed by the controller action.
- Keep shared private helpers inside the dedicated service when they are genuinely local. Extract shared collaborators only when multiple services need the same behavior.

## Symfony And PHP Practices

- Use strict types in all PHP files.
- Use constructor injection and `readonly` dependencies where practical.
- Use attributes for routing, validation, and Doctrine mapping.
- Use `#[MapRequestPayload]` for JSON request DTOs.
- Use `#[MapQueryString]` for validated query DTOs.
- Return consistent JSON errors. Validation failures should use the existing RFC 7807-style format with `violations`.
- Prefer PHP enums for fixed value sets; keep backed enum storage aligned with `../docs/db-diagram.uml`.
- Keep code compatible with the PHP version declared in `composer.json`.
- Format PHP with PHP CS Fixer using the configured `@PER-CS3.0` ruleset.
- Keep PHPStan passing at level 10 using the configured Symfony extension.

## Validation Rules

Use DTO validation for API input:

- required fields
- string length and format
- email/date/time formats
- enum choices
- numeric ranges
- simple field-level checks

Use service/domain validation for business rules:

- entity existence
- teacher can teach subject
- schedule conflict checks
- capacity checks
- state transitions
- publication rules

Use database constraints as safety nets, not as the primary client-facing validation mechanism.

## Doctrine Rules

- Do not deserialize API input directly into Doctrine entities.
- Do not return Doctrine entities directly from controllers.
- Keep repositories focused on queries and persistence access.
- Use transactions in services for multi-entity mutations.
- Keep entity relationships synchronized through entity methods when available.
- Add migrations when changing mapped schema. Do not edit old migrations after they have been shared.

## API Response Rules

- Always return a response resource object from services or dedicated response mappers. Do not return raw arrays as the API contract.
- Resource objects should define the public response shape and be serialized by the controller/framework.
- Do not leak password hashes, internal tokens, or persistence-only fields.
- Keep public endpoints unauthenticated only when the route is explicitly public.
- Public schedule data must only expose published schedules.
- Update `../docs/04-api-contracts.md` when routes, query parameters, request bodies, or response bodies change.

## Tests

Add tests at the level where behavior matters:

- Controller tests for HTTP contracts, authentication, validation, and response shape.
- Service tests for complex business rules when controller setup becomes too heavy.
- Repository tests only for non-trivial query behavior.

Use focused fixtures inside tests. Avoid relying on test execution order.

## REST API Verification

From `rest-api/`:

```bash
php bin/phpunit
php bin/console lint:container
php bin/console doctrine:schema:validate --skip-sync
vendor/bin/phpstan analyse --debug --memory-limit=1G
vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff --using-cache=no --sequential --allow-unsupported-php-version=yes
```

Use `--debug` for PHPStan in sandboxed environments where its parallel worker cannot bind to localhost.
Use `--sequential` for PHP CS Fixer in sandboxed environments where its parallel worker cannot bind to localhost.
