# API Contracts

This document describes the intended REST API surface. Exact routes may evolve during implementation, but task docs should update this file when routes are added or changed.

## General Rules

- Request and response bodies use JSON.
- Admin routes require authentication.
- Public schedule routes do not require authentication.
- Validation errors return RFC 7807-style JSON with field-level `violations`.
- Mutation endpoints should be transaction-safe.

Validation error response:

```json
{
  "type": "https://university-schedule.local/problems/validation-error",
  "title": "Validation failed",
  "status": 422,
  "violations": [
    {
      "propertyPath": "name",
      "message": "This value should not be blank."
    }
  ]
}
```

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

## Admin Entities

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
- `weekStart`: ISO date for Monday

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

Only published schedules are returned. If multiple published schedules overlap the requested week, the newest schedule by `publishedAt` is used. If no published schedule exists for the requested week, the response contains an empty `items` array.

### GET `/api/public/groups`

Returns groups available for schedule filtering.

### GET `/api/public/teachers`

Returns teachers available for schedule filtering.

### GET `/api/public/rooms`

Returns rooms available for schedule filtering.

## Schedule Administration

### POST `/api/admin/schedules`

Creates a draft schedule.

Request:

```json
{
  "semesterId": 1,
  "validFrom": "2026-09-01",
  "validTo": "2026-12-31"
}
```

Response:

```json
{
  "id": 12,
  "semesterId": 1,
  "status": "draft",
  "validFrom": "2026-09-01",
  "validTo": "2026-12-31",
  "createdBy": 1,
  "createdAt": "2026-05-13T13:00:00+00:00",
  "publishedAt": null,
  "entries": []
}
```

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

Response:

```json
{
  "id": 22,
  "scheduleId": 12,
  "subjectId": 4,
  "teacherId": 7,
  "lessonType": "laboratory",
  "roomId": 3,
  "timeSlotId": 2,
  "dayOfWeek": 1,
  "weekParity": "both",
  "groupIds": [1],
  "teachingLoadIds": [41]
}
```

### PATCH `/api/admin/schedules/{id}/entries/{entryId}`

Updates a schedule entry. The request accepts the same fields as creation, and fields may be omitted for partial updates.

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

The Go schedule service is the source of truth for full schedule validation. Symfony sends a normalized schedule snapshot to the service and returns the resulting validation resource.

### GET `/api/admin/schedules/{id}/lesson-cards`

Returns card data for the table-first admin editor. Cards are derived from teaching loads and scheduled entries.

The first implementation counts `weekParity: "both"` as two scheduled lessons and `odd` or `even` as one scheduled lesson for lesson-card progress.

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

Publishing delegates validation to the Go schedule service. A successful publish changes the schedule status to `published`, sets `publishedAt`, and writes an action-log entry.

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
  "id": "uuid",
  "semesterId": 1,
  "requestedBy": 1,
  "status": "queued",
  "generatedScheduleId": null,
  "qualityScore": null,
  "qualityStatus": null,
  "errorMessage": null,
  "diagnostics": null,
  "createdAt": "2026-05-13T12:00:00+00:00",
  "startedAt": null,
  "finishedAt": null
}
```

### GET `/api/admin/generation-jobs/{jobId}`

Returns generation status and generated draft information when available.

Response:

```json
{
  "id": "uuid",
  "semesterId": 1,
  "requestedBy": 1,
  "status": "completed",
  "generatedScheduleId": 12,
  "qualityScore": 92,
  "qualityStatus": "acceptable",
  "errorMessage": null,
  "diagnostics": {
    "generatedEntryCount": 24,
    "minimumQualityScore": 80
  },
  "createdAt": "2026-05-13T12:00:00+00:00",
  "startedAt": "2026-05-13T12:00:01+00:00",
  "finishedAt": "2026-05-13T12:00:03+00:00"
}
```

Generation job statuses are `queued`, `running`, `completed`, and `failed`. Generated schedules are saved as reviewable drafts and are not published automatically.

## Exam Scheduling

### POST `/api/admin/exam-schedules`

Creates a draft exam schedule.

Request:

```json
{
  "semesterId": 1
}
```

### GET `/api/admin/exam-schedules`

Lists active exam schedules.

Query parameters:

- `semesterId` optional

### GET `/api/admin/exam-schedules/{id}`

Returns an exam schedule with active entries.

### DELETE `/api/admin/exam-schedules/{id}`

Soft-deletes an exam schedule and its active entries.

### POST `/api/admin/exam-schedules/{id}/entries`

Creates an exam schedule entry.

Request:

```json
{
  "type": "exam",
  "subjectId": 4,
  "teacherId": 7,
  "roomId": 3,
  "groupIds": [1],
  "entryDate": "2026-12-21",
  "startsAt": "09:00:00"
}
```

`type` is `consultation` or `exam`.

### PATCH `/api/admin/exam-schedules/{id}/entries/{entryId}`

Updates an exam schedule entry.

### DELETE `/api/admin/exam-schedules/{id}/entries/{entryId}`

Soft-deletes an exam schedule entry.

### POST `/api/admin/exam-schedules/{id}/validate`

Validates an exam schedule.

Response:

```json
{
  "valid": false,
  "conflicts": [
    {
      "type": "consultation_missing",
      "message": "Exam requires a matching consultation 1 day(s) before the exam.",
      "entryIds": [12]
    }
  ]
}
```

### POST `/api/admin/exam-schedules/generate`

Starts automatic exam schedule generation for a semester.

Request:

```json
{
  "semesterId": 1
}
```

Response:

```json
{
  "id": "uuid",
  "semesterId": 1,
  "requestedBy": 1,
  "status": "queued",
  "generatedExamScheduleId": null,
  "qualityScore": null,
  "qualityStatus": null,
  "errorMessage": null,
  "diagnostics": null,
  "createdAt": "2026-05-13T12:00:00+00:00",
  "startedAt": null,
  "finishedAt": null
}
```

### GET `/api/admin/exam-schedule-generation-jobs/{jobId}`

Returns automatic exam generation status and generated draft information when available.

Response:

```json
{
  "id": "uuid",
  "semesterId": 1,
  "requestedBy": 1,
  "status": "completed",
  "generatedExamScheduleId": 14,
  "qualityScore": 100,
  "qualityStatus": "acceptable",
  "errorMessage": null,
  "diagnostics": {
    "generatedEntryCount": 18,
    "minimumQualityScore": 80
  },
  "createdAt": "2026-05-13T12:00:00+00:00",
  "startedAt": "2026-05-13T12:00:01+00:00",
  "finishedAt": "2026-05-13T12:00:03+00:00"
}
```

Exam generation job statuses are `queued`, `running`, `completed`, and `failed`. Generated exam schedules are saved as reviewable drafts and are not published automatically.

## Telegram Webhook

### POST `/api/telegram/webhook`

Receives Telegram updates. This endpoint is called by Telegram, not the frontend.

Security requirements:

- Verify Telegram webhook secret token or equivalent signature mechanism.
- Do not expose admin operations.
