# API Contracts

This document describes the intended REST API surface. Exact routes may evolve during implementation, but task docs should update this file when routes are added or changed.

## General Rules

- Request and response bodies use JSON.
- Admin routes require authentication.
- Public schedule routes do not require authentication.
- Validation errors return field-level details where possible.
- Mutation endpoints should be transaction-safe.

## Authentication

### POST `/api/auth/login`

Authenticates an administrator.

Request:

```json
{
  "email": "admin@example.com",
  "password": "secret"
}
```

Response:

```json
{
  "token": "string",
  "admin": {
    "id": 1,
    "firstName": "Ada",
    "lastName": "Lovelace",
    "email": "admin@example.com"
  }
}
```

Authentication uses JWT for the first release.

### GET `/api/auth/me`

Returns the current administrator.

## Reference Data

Use conventional CRUD endpoints for:

- `/api/admin/groups`
- `/api/admin/teachers`
- `/api/admin/subjects`
- `/api/admin/teaching-loads`
- `/api/admin/rooms`
- `/api/admin/time-slots`
- `/api/admin/academic-years`
- `/api/admin/semesters`

List responses should support pagination or at least predictable sorting.

### POST `/api/admin/teaching-loads`

Creates a semester teaching-load requirement.

Request:

```json
{
  "semesterId": 1,
  "groupId": 1,
  "subjectId": 4,
  "teacherId": 7,
  "lessonType": "laboratory",
  "requiredLessonCount": 8
}
```

Response:

```json
{
  "id": 41,
  "semesterId": 1,
  "groupId": 1,
  "subjectId": 4,
  "teacherId": 7,
  "lessonType": "laboratory",
  "requiredLessonCount": 8
}
```

Teaching-load endpoints should also support list, update, and soft delete/archive behavior.

## Public Schedule

### GET `/api/public/schedule`

Returns a weekly schedule grid.

Query parameters:

- `type`: `group`, `teacher`, or `room`
- `id`: entity id
- `weekStart`: ISO date for Monday or configured week start

Response:

```json
{
  "weekStart": "2026-09-07",
  "type": "group",
  "id": 1,
  "items": [
    {
      "id": 123,
      "date": "2026-09-07",
      "dayOfWeek": 1,
      "timeSlot": {
        "id": 1,
        "number": 1,
        "startsAt": "08:30",
        "endsAt": "10:00"
      },
      "subject": {
        "id": 4,
        "name": "Algorithms"
      },
      "teacher": {
        "id": 7,
        "firstName": "Grace",
        "lastName": "Hopper"
      },
      "room": {
        "id": 3,
        "name": "Lab 1",
        "type": "computer"
      },
      "groups": [
        {
          "id": 1,
          "name": "KN-22"
        }
      ],
      "isCancelled": false,
      "isOverride": false
    }
  ]
}
```

### GET `/api/public/groups`

Returns groups available for schedule filtering.

### GET `/api/public/teachers`

Returns teachers available for schedule filtering.

### GET `/api/public/rooms`

Returns rooms available for schedule filtering.

## Schedule Administration

### POST `/api/admin/schedules`

Creates a draft schedule.

### GET `/api/admin/schedules/{id}`

Returns schedule details.

### POST `/api/admin/schedules/{id}/entries`

Creates a schedule entry.

Request:

```json
{
  "teachingLoadIds": [41],
  "subjectId": 4,
  "teacherId": 7,
  "lessonType": "laboratory",
  "roomId": 3,
  "timeSlotId": 2,
  "dayOfWeek": 1,
  "weekParity": "both",
  "groupIds": [1]
}
```

### PATCH `/api/admin/schedules/{id}/entries/{entryId}`

Updates a schedule entry.

### DELETE `/api/admin/schedules/{id}/entries/{entryId}`

Deletes a schedule entry.

### POST `/api/admin/schedules/{id}/validate`

Returns validation result without publishing.

Response:

```json
{
  "valid": false,
  "conflicts": [
    {
      "type": "teacher_conflict",
      "message": "Teacher is already assigned at this time.",
      "entryIds": [12, 18]
    }
  ]
}
```

Validation should include both conflict checks and teaching-load completion checks.

### GET `/api/admin/schedules/{id}/lesson-cards`

Returns card data for the table-first admin editor. Cards are derived from teaching loads and scheduled entries.

Response:

```json
{
  "items": [
    {
      "teachingLoadId": 41,
      "group": {
        "id": 1,
        "name": "KN-22"
      },
      "subject": {
        "id": 4,
        "name": "Programming"
      },
      "teacher": {
        "id": 7,
        "name": "John Doe"
      },
      "lessonType": "laboratory",
      "requiredLessonCount": 8,
      "scheduledLessonCount": 4,
      "remainingLessonCount": 4
    }
  ]
}
```

### POST `/api/admin/schedules/{id}/publish`

Publishes a valid schedule. Must fail if validation fails.

## Generation

### POST `/api/admin/schedules/generate`

Starts asynchronous generation.

Request:

```json
{
  "semesterId": 1
}
```

Response:

```json
{
  "jobId": "uuid",
  "status": "queued"
}
```

### GET `/api/admin/generation-jobs/{jobId}`

Returns generation status and generated draft information when available.

## Exam Scheduling

### POST `/api/admin/exams`

Creates an exam.

Request:

```json
{
  "semesterId": 1,
  "subjectId": 4,
  "teacherId": 7,
  "roomId": 3,
  "groupIds": [1],
  "examDate": "2026-12-21",
  "startsAt": "09:00"
}
```

### GET `/api/admin/exams`

Lists exams for a semester.

Query parameters:

- `semesterId`
- `groupId` optional
- `teacherId` optional

### PATCH `/api/admin/exams/{id}`

Updates an exam.

### DELETE `/api/admin/exams/{id}`

Deletes or cancels an exam according to the selected history policy.

### POST `/api/admin/exams/generate`

Starts automatic exam schedule generation for a semester.

Response:

```json
{
  "jobId": "uuid",
  "status": "queued"
}
```

## Telegram Webhook

### POST `/api/telegram/webhook`

Receives Telegram updates. This endpoint is called by Telegram, not the frontend.

Security requirements:

- Verify Telegram webhook secret token or equivalent signature mechanism.
- Do not expose admin operations.
