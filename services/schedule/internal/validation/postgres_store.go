package validation

import (
	"context"
	"database/sql"
	"fmt"
	"sort"

	_ "github.com/jackc/pgx/v5/stdlib"
)

type PostgresStore struct {
	db *sql.DB
}

func NewPostgresStore(databaseURL string) (*PostgresStore, error) {
	db, err := sql.Open("pgx", databaseURL)
	if err != nil {
		return nil, fmt.Errorf("open database: %w", err)
	}

	return &PostgresStore{db: db}, nil
}

func (store *PostgresStore) Close() error {
	return store.db.Close()
}

func (store *PostgresStore) LoadSchedule(ctx context.Context, scheduleID int64) (Schedule, error) {
	schedule, err := store.loadScheduleInfo(ctx, scheduleID)
	if err != nil {
		return Schedule{}, err
	}

	entries, err := store.loadEntries(ctx, scheduleID)
	if err != nil {
		return Schedule{}, err
	}

	if err := store.attachGroups(ctx, scheduleID, entries); err != nil {
		return Schedule{}, err
	}

	if err := store.attachTeachingLoadIDs(ctx, scheduleID, entries); err != nil {
		return Schedule{}, err
	}

	teachingLoads, err := store.loadTeachingLoads(ctx, scheduleID)
	if err != nil {
		return Schedule{}, err
	}

	assignments, err := store.loadTeacherSubjectAssignments(ctx)
	if err != nil {
		return Schedule{}, err
	}

	unavailability, err := store.loadTeacherUnavailability(ctx)
	if err != nil {
		return Schedule{}, err
	}

	schedule.Entries = values(entries)
	schedule.TeachingLoads = teachingLoads
	schedule.TeacherSubjectAssignments = assignments
	schedule.TeacherUnavailabilityRules = unavailability

	return schedule, nil
}

func (store *PostgresStore) loadScheduleInfo(ctx context.Context, scheduleID int64) (Schedule, error) {
	var schedule Schedule
	if err := store.db.QueryRowContext(ctx, `
		SELECT s.id, ss.starts_at::text, ss.ends_at::text, s.valid_from::text, s.valid_to::text
		FROM schedules s
		INNER JOIN semesters ss ON ss.id = s.semester_id
		WHERE s.id = $1
	`, scheduleID).Scan(&schedule.ID, &schedule.SemesterStartsAt, &schedule.SemesterEndsAt, &schedule.ValidFrom, &schedule.ValidTo); err != nil {
		return Schedule{}, fmt.Errorf("load schedule: %w", err)
	}

	return schedule, nil
}

func (store *PostgresStore) loadEntries(ctx context.Context, scheduleID int64) (map[int64]ScheduleEntry, error) {
	rows, err := store.db.QueryContext(ctx, `
		SELECT
			se.id,
			se.subject_id,
			se.teacher_id,
			se.lesson_type,
			se.room_id,
			r.capacity,
			se.time_slot_id,
			to_char(ts.starts_at, 'HH24:MI:SS'),
			to_char(ts.ends_at, 'HH24:MI:SS'),
			se.day_of_week,
			se.week_parity
		FROM schedule_entries se
		INNER JOIN rooms r ON r.id = se.room_id
		INNER JOIN time_slots ts ON ts.id = se.time_slot_id
		WHERE se.schedule_id = $1
		ORDER BY se.id ASC
	`, scheduleID)
	if err != nil {
		return nil, fmt.Errorf("load schedule entries: %w", err)
	}
	defer rows.Close()

	entries := make(map[int64]ScheduleEntry)

	for rows.Next() {
		var entry ScheduleEntry
		var lessonType int
		var weekParity int

		if err := rows.Scan(
			&entry.ID,
			&entry.SubjectID,
			&entry.TeacherID,
			&lessonType,
			&entry.RoomID,
			&entry.RoomCapacity,
			&entry.TimeSlotID,
			&entry.TimeSlotStartsAt,
			&entry.TimeSlotEndsAt,
			&entry.DayOfWeek,
			&weekParity,
		); err != nil {
			return nil, fmt.Errorf("scan schedule entry: %w", err)
		}

		entry.LessonType = lessonTypeName(lessonType)
		entry.WeekParity = weekParityName(weekParity)
		entries[entry.ID] = entry
	}

	if err := rows.Err(); err != nil {
		return nil, fmt.Errorf("iterate schedule entries: %w", err)
	}

	return entries, nil
}

func (store *PostgresStore) attachGroups(ctx context.Context, scheduleID int64, entries map[int64]ScheduleEntry) error {
	rows, err := store.db.QueryContext(ctx, `
		SELECT seg.schedule_entry_id, g.id, g.student_count
		FROM schedule_entry_groups seg
		INNER JOIN schedule_entries se ON se.id = seg.schedule_entry_id
		INNER JOIN groups g ON g.id = seg.group_id
		WHERE se.schedule_id = $1
		ORDER BY seg.schedule_entry_id ASC, g.id ASC
	`, scheduleID)
	if err != nil {
		return fmt.Errorf("load schedule entry groups: %w", err)
	}
	defer rows.Close()

	for rows.Next() {
		var entryID int64
		var groupID int64
		var studentCount int

		if err := rows.Scan(&entryID, &groupID, &studentCount); err != nil {
			return fmt.Errorf("scan schedule entry group: %w", err)
		}

		entry := entries[entryID]
		entry.GroupIDs = append(entry.GroupIDs, groupID)
		entry.StudentCount += studentCount
		entries[entryID] = entry
	}

	return rows.Err()
}

func (store *PostgresStore) attachTeachingLoadIDs(ctx context.Context, scheduleID int64, entries map[int64]ScheduleEntry) error {
	rows, err := store.db.QueryContext(ctx, `
		SELECT setl.schedule_entry_id, setl.teaching_load_id
		FROM schedule_entry_teaching_loads setl
		INNER JOIN schedule_entries se ON se.id = setl.schedule_entry_id
		WHERE se.schedule_id = $1
		ORDER BY setl.schedule_entry_id ASC, setl.teaching_load_id ASC
	`, scheduleID)
	if err != nil {
		return fmt.Errorf("load schedule entry teaching loads: %w", err)
	}
	defer rows.Close()

	for rows.Next() {
		var entryID int64
		var teachingLoadID int64

		if err := rows.Scan(&entryID, &teachingLoadID); err != nil {
			return fmt.Errorf("scan schedule entry teaching load: %w", err)
		}

		entry := entries[entryID]
		entry.TeachingLoadIDs = append(entry.TeachingLoadIDs, teachingLoadID)
		entries[entryID] = entry
	}

	return rows.Err()
}

func (store *PostgresStore) loadTeachingLoads(ctx context.Context, scheduleID int64) ([]TeachingLoad, error) {
	rows, err := store.db.QueryContext(ctx, `
		SELECT tl.id, tl.group_id, tl.subject_id, tl.teacher_id, tl.lesson_type, tl.required_lesson_count
		FROM teaching_loads tl
		INNER JOIN schedules s ON s.semester_id = tl.semester_id
		WHERE s.id = $1 AND tl.deleted_at IS NULL
		ORDER BY tl.id ASC
	`, scheduleID)
	if err != nil {
		return nil, fmt.Errorf("load teaching loads: %w", err)
	}
	defer rows.Close()

	teachingLoads := make([]TeachingLoad, 0)

	for rows.Next() {
		var teachingLoad TeachingLoad
		var lessonType int

		if err := rows.Scan(
			&teachingLoad.ID,
			&teachingLoad.GroupID,
			&teachingLoad.SubjectID,
			&teachingLoad.TeacherID,
			&lessonType,
			&teachingLoad.RequiredLessonCount,
		); err != nil {
			return nil, fmt.Errorf("scan teaching load: %w", err)
		}

		teachingLoad.LessonType = lessonTypeName(lessonType)
		teachingLoads = append(teachingLoads, teachingLoad)
	}

	return teachingLoads, rows.Err()
}

func (store *PostgresStore) loadTeacherSubjectAssignments(ctx context.Context) ([]TeacherSubject, error) {
	rows, err := store.db.QueryContext(ctx, `
		SELECT teacher_id, subject_id
		FROM teacher_subjects
		ORDER BY teacher_id ASC, subject_id ASC
	`)
	if err != nil {
		return nil, fmt.Errorf("load teacher subject assignments: %w", err)
	}
	defer rows.Close()

	assignments := make([]TeacherSubject, 0)

	for rows.Next() {
		var assignment TeacherSubject
		if err := rows.Scan(&assignment.TeacherID, &assignment.SubjectID); err != nil {
			return nil, fmt.Errorf("scan teacher subject assignment: %w", err)
		}

		assignments = append(assignments, assignment)
	}

	return assignments, rows.Err()
}

func (store *PostgresStore) loadTeacherUnavailability(ctx context.Context) ([]TeacherUnavailability, error) {
	rows, err := store.db.QueryContext(ctx, `
		SELECT teacher_id, day_of_week, to_char(unavailable_from, 'HH24:MI:SS'), to_char(unavailable_to, 'HH24:MI:SS')
		FROM teacher_unavailability
		ORDER BY teacher_id ASC, day_of_week ASC
	`)
	if err != nil {
		return nil, fmt.Errorf("load teacher unavailability: %w", err)
	}
	defer rows.Close()

	unavailability := make([]TeacherUnavailability, 0)

	for rows.Next() {
		var rule TeacherUnavailability
		if err := rows.Scan(&rule.TeacherID, &rule.DayOfWeek, &rule.UnavailableFrom, &rule.UnavailableTo); err != nil {
			return nil, fmt.Errorf("scan teacher unavailability: %w", err)
		}

		unavailability = append(unavailability, rule)
	}

	return unavailability, rows.Err()
}

func values(entries map[int64]ScheduleEntry) []ScheduleEntry {
	result := make([]ScheduleEntry, 0, len(entries))

	for _, entry := range entries {
		result = append(result, entry)
	}
	sort.Slice(result, func(left int, right int) bool {
		return result[left].ID < result[right].ID
	})

	return result
}

func lessonTypeName(value int) string {
	return LessonTypeName(value)
}

func weekParityName(value int) string {
	return WeekParityName(value)
}

func LessonTypeName(value int) string {
	switch value {
	case 1:
		return "lecture"
	case 2:
		return "laboratory"
	case 3:
		return "seminar"
	case 4:
		return "practical"
	default:
		return "unknown"
	}
}

func WeekParityName(value int) string {
	switch value {
	case 1:
		return "odd"
	case 2:
		return "even"
	case 3:
		return "both"
	default:
		return "unknown"
	}
}
