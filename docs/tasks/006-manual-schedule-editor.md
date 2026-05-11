# Task: Manual Schedule Editor

## Goal

Allow administrators to create and edit draft schedule entries manually.

## Context

Read before starting:

- `docs/01-requirements.md`
- `docs/03-data-model.md`
- `docs/04-api-contracts.md`
- `docs/08-frontend-ux.md`

Manual editing is the control surface administrators need even if automatic generation exists later.

## Scope

- Backend endpoints for creating, updating, and deleting schedule entries.
- Frontend admin screen for schedule entry management.
- Lesson-card API derived from teaching-load requirements.
- Selection of teaching load, subject, teacher, lesson type, room, time slot, day, week parity, and groups.
- Drag-and-drop placement of lesson cards into table cells.
- Card resizing or recurrence editing for both weeks, odd weeks, and even weeks.
- Field-level validation.
- Basic conflict feedback from API.

## Out Of Scope

- Automatic generation.
- Telegram notifications.
- Exam scheduling.

## Acceptance Criteria

- Admin can create, edit, and delete draft entries.
- Admin can see required, scheduled, and remaining lesson counts.
- Placing a card creates or updates a linked schedule entry.
- Changing card week parity changes how many semester occurrences it contributes.
- Invalid entries are rejected with useful messages.
- A draft schedule can be viewed after changes.
- Public users do not see draft entries.

## Suggested Files Or Areas

- `rest-api/src/Controller`
- `rest-api/src/Entity/Schedule.php`
- `rest-api/src/Entity/ScheduleEntry.php`
- `frontend/src`
