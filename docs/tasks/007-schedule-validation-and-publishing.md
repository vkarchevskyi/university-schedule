# Task: Schedule Validation And Publishing

## Goal

Prevent invalid schedules from becoming public and provide administrators with actionable conflict reports.

## Context

Read before starting:

- `docs/01-requirements.md`
- `docs/03-data-model.md`
- `docs/04-api-contracts.md`
- `docs/06-scheduling-engine.md`

Validation is the trust boundary of the product.

The Go schedule service owns the hard schedule validation rules because automatic generation needs the same rule implementation. Symfony exposes the admin API boundary and owns publishing state changes, but delegates full schedule validation to Go before publishing.

## Scope

- Implement reusable validation logic in the Go schedule service.
- Add a Symfony validation client and schedule snapshot payload builder.
- Detect hard constraint conflicts.
- Detect unsatisfied teaching-load requirements.
- Expose validation endpoint.
- Block publication when validation fails.
- Log successful publication.
- Invalidate public schedule cache if caching exists.

## Out Of Scope

- Automatic generation.
- Soft-constraint scoring unless easy to include.
- Exam validation.

## Acceptance Criteria

- Group, teacher, and room time conflicts are detected.
- Room capacity conflicts are detected.
- Teacher-subject mismatch is detected.
- Teacher unavailability is detected.
- Missing or over-scheduled teaching-load counts are detected.
- Invalid schedules cannot be published.
- Valid schedules can be published and become public.

## Suggested Files Or Areas

- `services/schedule`
- `rest-api/src/Service`
- `rest-api/src/Controller`
- `rest-api/src/Enum/ScheduleStatus.php`
- `rest-api/tests`
