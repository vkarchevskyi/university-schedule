# Task: Entity CRUD

## Goal

Implement administrative CRUD for the academic entities required to build schedules.

## Context

Read before starting:

- `docs/01-requirements.md`
- `docs/03-data-model.md`
- `docs/04-api-contracts.md`

Schedule creation depends on clean entities.

## Scope

- Groups CRUD.
- Teachers CRUD.
- Subjects CRUD.
- Teacher-subject assignment.
- Teaching-load requirement CRUD for semester, group, subject, teacher, lesson type, and required lesson count.
- Teacher unavailability management.
- Rooms CRUD.
- Time slots CRUD.
- Academic years and semesters CRUD.
- Basic validation for required fields and date/time consistency.

## Out Of Scope

- Schedule editing.
- Schedule generation.
- Public schedule grid.
- Frontend admin screens unless this task is explicitly expanded.

## Acceptance Criteria

- Admin can manage all academic entities through API endpoints.
- Admin can define that a group must receive a required number of lectures, labs, seminars, or practical classes for a subject with a teacher during a semester.
- Invalid data returns useful validation errors.
- Entities used by schedules cannot be deleted in a way that breaks existing schedule history.
- Tests cover representative create, update, list, and validation cases.

## Suggested Files Or Areas

- `rest-api/src/Entity`
- `rest-api/src/Repository`
- `rest-api/src/Controller`
- `rest-api/tests`

## Implementation Notes

- Prefer request DTO validation with Symfony Validator and `#[MapRequestPayload]`. Keep entity existence and business rules in services.
- Be careful with the entity named `Group`, because `group` can be a reserved or awkward term in some contexts.
