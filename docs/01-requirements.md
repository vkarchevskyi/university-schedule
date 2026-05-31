# Requirements

## Functional Requirements

### Authentication And Access

- The system supports administrator authentication by login and password.
- The Symfony API uses JWT for administrator authentication.
- Administrative operations are available only to authenticated administrators.
- Public schedule viewing does not require authentication.
- Telegram users are not administrator accounts.

### Academic Entity Management

- Administrators can create, view, update, and delete groups.
- Groups include name, speciality, course, and student count.
- Administrators can manage teachers.
- Teachers include first name, last name, department, taught subjects, and unavailable time ranges.
- Administrators can manage rooms.
- Rooms include name, type, and capacity.
- Administrators can manage subjects.
- Subjects can be associated with teachers and schedule entries.
- Administrators can manage teaching-load requirements for a semester.
- Teaching-load requirements must use teacher and subject pairs that are associated with each other.
- Teaching-load requirements define how many lessons of each lesson type a group must receive for a subject with a teacher during a semester.
- Lesson types include lecture, laboratory work, seminar, and other configured types if added later.
- Administrators can manage academic years and semesters.
- Semesters include start date, end date, and first week parity.
- Administrators can manage time slots.
- Time slots include number, start time, and end time.

### Schedule Formation

- Administrators can manually create schedule entries.
- A schedule entry includes subject, teacher, lesson type, room, weekday (1-5, Monday-Friday), week parity, and one or more groups.
- Schedule entries should be linkable to the teaching-load requirement they satisfy.
- The admin editor should expose lesson cards derived from teaching-load requirements and already scheduled entries.
- Lesson cards can be placed into the table schedule and resized or adjusted through week parity: both weeks, odd weeks, or even weeks.
- Administrators can edit and delete entries.
- The system validates conflicts before saving or publishing.
- The system validates whether scheduled entries satisfy required lesson counts for each teaching-load requirement.
- The system can request automatic schedule generation for a selected semester.
- Automatic schedule generation requests are accepted only when the selected semester has active teaching-load requirements and the supporting rooms, time slots, and teacher-subject associations required by the generator.
- Automatic schedule generation is required for the first shipped version.
- Generated schedules are reviewed and confirmed by an administrator before becoming official.

### Exam Session

- Administrators can manually schedule exams.
- Exams include semester, subject, teacher, room, date, start time, and one or more groups.
- Exam scheduling is required for the first shipped version.
- Automatic exam generation should be supported for this release, with constraints such as minimum interval between exams for the same group.

### Public Schedule Viewing

- Users can view the current published schedule as a weekly grid.
- Users can filter by group, teacher, room, and week.
- Public views should work without authentication.
- Administrators can audit schedule changes through an action log.

### Telegram Bot

- The bot supports `/start`.
- The bot supports `/schedule`.
- The bot supports `/subscribe` and `/unsubscribe`.
- Users can subscribe to changes for a group or teacher.
- The bot sends notifications when a subscribed schedule changes.
- The bot supports free-text schedule questions through AI intent parsing.

### AI Interface

- AI interprets natural-language requests into structured intents.
- Gemini API is the selected first-release LLM provider.
- Supported intents should include schedule lookup and general help.
- AI output must be validated before use.
- Schedule answers must be based on current API/database data, not model memory.

## Nonfunctional Requirements

### Performance

- Typical schedule-view requests should respond quickly enough for interactive web usage.
- Frequently requested current-week schedules should be cacheable through Redis.
- Long-running schedule generation should be asynchronous.

### Reliability And Data Integrity

- Schedule-changing operations should use database transactions.
- Invalid schedules must not be publishable.
- Schedule changes should be logged.
- Generation failures should not corrupt the current published schedule.

### Security

- Admin operations require authentication and authorization.
- Public users cannot mutate data.
- Secrets must be stored outside source code.
- Telegram webhook requests should be verified.
- AI responses must not be trusted as authoritative without schema validation.

### Usability

- Public schedule views should work on desktop and mobile.
- Admin workflows should support common operations without external documentation.
- Errors should be shown near form fields where possible.
- Schedule conflicts should be visible and actionable.

### Deployment And Operations

- Components should be isolated so that one service can be restarted without taking down the entire system.
- Configuration should be environment-based.
- PostgreSQL, Redis, and RabbitMQ should be deployable locally for development.
- The first release targets VPS deployment with Docker Compose.
- HTTPS is required for deployed web access and Telegram webhooks.
