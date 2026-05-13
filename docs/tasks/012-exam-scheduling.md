# Task: Exam Scheduling

## Goal

Implement first-release exam scheduling, including manual exam management and automatic exam generation.

## Context

Read before starting:

- `docs/01-requirements.md`
- `docs/03-data-model.md`
- `docs/04-api-contracts.md`
- `docs/06-scheduling-engine.md`

Exam scheduling is required for the first shipped version.

## Scope

- Add admin API endpoints for exam schedule CRUD.
- Support exam schedule entries with type `consultation` or `exam`.
- Support subject, teacher, room, date, start time, and one or more groups on entries.
- Soft-delete exam schedules and entries.
- Validate teacher, group, and room conflicts for exams.
- Validate room capacity.
- Validate teacher-subject compatibility.
- Validate minimum interval between exams for the same group.
- Validate that every exam has a matching consultation entry before it.
- Make consultation offset and minimum interval configurable.
- Add automatic exam generation using the same job/worker architecture where practical.
- Add table-first admin UI for exam management.
- Add public or admin exam viewing if needed for the shipped demo.

## Out Of Scope

- Student accounts.
- Complex exam preference optimization beyond the first useful constraint set.
- AI-generated exams.

## Acceptance Criteria

- Admin can create, edit, and delete or cancel exams.
- Invalid exams are rejected with actionable validation errors.
- Automatic exam generation creates a reviewable result.
- Generated exams are not published blindly if publication/review state is used.
- Exam scheduling works in the deployed release.

## Suggested Files Or Areas

- `rest-api/src/Entity/ExamSchedule.php`
- `rest-api/src/Entity/ExamScheduleEntry.php`
- `rest-api/src/Entity/ExamScheduleEntryGroup.php`
- `rest-api/src/Controller`
- `rest-api/src/Service`
- `services/schedule`
- `frontend/src`

## Verification

- Backend tests cover manual exam validation.
- Generation tests or fixtures cover minimum interval constraints.
- Frontend build passes.
