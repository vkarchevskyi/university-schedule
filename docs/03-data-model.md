# Data Model

The source of truth for the database scheme is `docs/db-diagram.uml`. Symfony entities under `rest-api/src/Entity` should be kept aligned with it.

## Core Entities

### User

Represents an authenticated account. Users have one role: `user` or `admin`.

Fields:

- id
- firstName
- lastName
- email
- passwordHash
- role
- createdAt

Relationships:

- creates schedules
- creates exams
- creates action log records

Rules:

- `user` grants the `ROLE_USER` authority.
- `admin` grants both `ROLE_USER` and `ROLE_ADMIN` authorities.

### TelegramSubscription

Represents a Telegram user subscription to updates for a group or teacher.

Fields:

- id
- telegramChatId
- entityType
- entityId
- createdAt

Notes:

- `entityType` should be constrained to supported values such as `group` and `teacher`.
- Entity-type values should be represented in PHP as enums backed by tinyint database columns where practical.
- Duplicate subscriptions for the same chat and entity should be prevented.

### AcademicYear

Represents an academic year.

Fields:

- id
- name
- startsAt
- endsAt

Relationships:

- has many semesters

### Semester

Represents a semester within an academic year.

Fields:

- id
- academicYear
- number
- startsAt
- endsAt
- firstWeekParity

Notes:

- `firstWeekParity` is used to calculate numerator/denominator week parity.
- Week parity should be represented in PHP as an enum backed by a tinyint database column.

### Group

Represents a student group.

Fields:

- id
- name
- speciality
- course
- studentCount

### Teacher

Represents a teacher.

Fields:

- id
- firstName
- lastName
- department

Relationships:

- has many teacher subject links
- has many unavailable time ranges

### TeacherUnavailability

Represents a teacher time restriction.

Fields:

- id
- teacher
- dayOfWeek
- unavailableFrom
- unavailableTo

### Subject

Represents an academic subject.

Fields:

- id
- name

### TeacherSubject

Represents a many-to-many assignment between teachers and subjects.

Fields:

- id
- teacher
- subject

### LessonType

Represents the type of class activity.

Expected values:

- lecture
- laboratory
- seminar
- practical

Implementation decision:

- Lesson type should be represented in PHP as an enum backed by a tinyint database column.

### TeachingLoad

Represents a semester-level requirement for how many lessons of a specific type must be scheduled for a group, subject, and teacher.

Example:

- Group: KN-22
- Subject: Programming
- Teacher: John Doe
- Semester: 2026 autumn
- Lesson type: lecture
- Required lesson count: 8

Fields:

- id
- semester
- group
- subject
- teacher
- lessonType
- requiredLessonCount
- createdAt
- updatedAt
- deletedAt, if soft deletes are implemented through nullable timestamp

Notes:

- This table is the source for draggable lesson cards in the admin schedule editor.
- A card in the UI can represent unscheduled or partially scheduled teaching load.
- The card itself does not need to be persisted as a separate table for the first release unless the UI needs saved planning-board state.
- If combined lectures for multiple groups are required, represent one teaching-load row per group and allow one scheduled entry to satisfy multiple teaching-load rows through a join table.

### ScheduleEntryTeachingLoad

Join table between schedule entries and the teaching-load rows they satisfy.

Fields:

- scheduleEntry
- teachingLoad

Notes:

- This allows a single scheduled lecture for several groups to count toward each group's required lecture count.
- For simple single-group entries, the schedule entry usually links to one teaching-load row.
- Use this join table as the canonical link instead of a direct `teachingLoadId` field on `ScheduleEntry`.

### Room

Represents a classroom or lab.

Fields:

- id
- name
- type
- capacity

### TimeSlot

Represents a class period.

Fields:

- id
- number
- startsAt
- endsAt

## Schedule Entities

### Schedule

Represents a schedule for a semester and validity period.

Fields:

- id
- semester
- status
- validFrom
- validTo
- createdBy
- createdAt
- publishedAt

Expected statuses:

- draft
- generated
- published
- archived

Actual enum values should follow `rest-api/src/Enum/ScheduleStatus.php`.

Implementation decision:

- Schedule status should be represented in PHP as an enum backed by a tinyint database column.

### ScheduleEntry

Represents a recurring class in a schedule.

Fields:

- id
- schedule
- subject
- teacher
- lessonType
- room
- timeSlot
- dayOfWeek
- weekParity

Relationships:

- has one or more groups through `ScheduleEntryGroup`
- satisfies one or more teaching-load rows through `ScheduleEntryTeachingLoad`
- can generate dated lessons

Editing and read-model note:

- Schedule entries remain the authoritative editable representation.
- The frontend admin editor is table-first and edits schedule-entry data.
- Lesson cards in the UI should be derived from `TeachingLoad` rows and linked/scheduled `ScheduleEntry` rows.
- A card's effective quantity is calculated from the semester calendar and `weekParity`: both weeks usually produces more occurrences than odd-only or even-only entries.
- A materialized view of schedule-entry data may be used as a read model to simplify table rendering and public schedule queries, but it must not replace the source schedule-entry tables.

### ScheduleEntryGroup

Join table between schedule entries and groups.

Fields:

- scheduleEntry
- group

### Lesson

Represents a concrete dated class occurrence, including overrides.

Fields:

- id
- scheduleEntry
- lessonDate
- subject
- teacher
- lessonType
- room
- timeSlot
- isCancelled
- isOverride

Relationships:

- has one or more groups through `LessonGroup`

### LessonGroup

Join table between concrete lessons and groups.

Fields:

- lesson
- group

## Exam Entities

### ExamSchedule

Represents a draft or published exam-session schedule for one semester.

Fields:

- id
- semester
- status
- createdBy
- createdAt
- publishedAt
- deletedAt

Relationships:

- has many entries

### ExamScheduleEntry

Represents either a consultation or an exam record inside an exam schedule.

Fields:

- id
- examSchedule
- type: consultation or exam
- subject
- teacher
- room
- entryDate
- startsAt
- deletedAt

Relationships:

- has one or more groups through `ExamScheduleEntryGroup`

### ExamScheduleEntryGroup

Join table between exam schedule entries and groups.

Fields:

- examScheduleEntry
- group

Rules:

- exam schedules and entries use soft deletes
- exam entries require a matching consultation entry before the exam
- consultation offset is configurable and defaults to one day before the exam
- minimum days between exams for the same group is configurable
- teacher, group, room, capacity, and teacher-subject conflicts are validated before saving entries

### ExamScheduleGenerationJob

Tracks asynchronous automatic exam schedule generation.

Fields:

- id
- semester
- requestedBy
- status: queued, running, completed, or failed
- generatedExamSchedule
- qualityScore
- qualityStatus
- errorMessage
- diagnostics
- createdAt
- startedAt
- finishedAt

Rules:

- Go service consumes jobs from the exam schedule generation queue
- generated exam schedules are stored as drafts
- one demand is derived from each distinct semester, group, subject, and teacher teaching-load combination

## Audit Entity

### ActionLog

Records administrative actions.

Fields:

- id
- user
- action
- entityType
- entityId
- createdAt

## Data Integrity Rules

- Group, teacher, and room conflicts must be prevented for the same day, time slot, and applicable week parity.
- Room capacity must be greater than or equal to the total student count of all linked groups.
- A teacher should teach only subjects linked through `TeacherSubject`.
- A teacher should not be scheduled during unavailable time ranges.
- Schedule entries must be inside the semester and schedule validity period.
- Schedule entries linked to teaching load must match semester, subject, teacher, lesson type, and group requirements.
- A schedule should not be considered complete if scheduled occurrence counts do not satisfy teaching-load requirements.
- Published schedules must be valid.
- Schedule-changing operations should be logged.
- Entity used by historical schedules should use soft deletes or archival status instead of destructive deletion.
- When changing an existing published schedule, create a new schedule version based on the previous schedule, then archive or close the previous version rather than mutating history destructively.
