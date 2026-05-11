# Task: Public Schedule API

## Goal

Expose a public API for viewing the current published weekly schedule by group, teacher, or room.

## Context

Read before starting:

- `docs/01-requirements.md`
- `docs/03-data-model.md`
- `docs/04-api-contracts.md`

Public schedule viewing is the first user-visible value of the product.

## Scope

- Implement `GET /api/public/schedule`.
- Support filters by `type`, `id`, and `weekStart`.
- Return normalized weekly schedule items.
- Add public lookup endpoints for groups, teachers, and rooms.
- Use only published schedule data.
- Keep the route unauthenticated.

## Out Of Scope

- Admin schedule editing.
- Telegram formatting.
- Redis caching unless already available from a prior task.
- Frontend UI.

## Acceptance Criteria

- Users can fetch a weekly schedule without authentication.
- The response includes subject, teacher, room, groups, time slot, date/day, and cancellation/override flags.
- Invalid filters return clear errors.
- Unpublished draft data is not visible.
- Tests cover group, teacher, and room filtering.

## Suggested Files Or Areas

- `rest-api/src/Controller`
- `rest-api/src/Repository/ScheduleRepository.php`
- `rest-api/src/Repository/LessonRepository.php`
- `rest-api/tests`

