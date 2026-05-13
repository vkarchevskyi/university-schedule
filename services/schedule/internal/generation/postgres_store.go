package generation

import (
	"context"
	"database/sql"
	"encoding/json"
	"fmt"

	_ "github.com/jackc/pgx/v5/stdlib"
	"github.com/vkarchevskyi/university-schedule/services/schedule/internal/validation"
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

func (store *PostgresStore) MarkRunning(ctx context.Context, jobID string) error {
	_, err := store.db.ExecContext(ctx, `
		UPDATE schedule_generation_jobs
		SET status = 'running', started_at = NOW()
		WHERE id = $1
	`, jobID)

	return err
}

func (store *PostgresStore) MarkCompleted(ctx context.Context, jobID string, result Result) error {
	diagnostics, err := json.Marshal(result.Diagnostics)
	if err != nil {
		return fmt.Errorf("marshal diagnostics: %w", err)
	}

	_, err = store.db.ExecContext(ctx, `
		UPDATE schedule_generation_jobs
		SET status = 'completed',
			generated_schedule_id = $2,
			quality_score = $3,
			quality_status = $4,
			diagnostics = $5,
			finished_at = NOW()
		WHERE id = $1
	`, jobID, result.ScheduleID, result.QualityScore, result.QualityStatus, string(diagnostics))

	return err
}

func (store *PostgresStore) MarkFailed(ctx context.Context, jobID string, message string) error {
	_, err := store.db.ExecContext(ctx, `
		UPDATE schedule_generation_jobs
		SET status = 'failed', error_message = $2, finished_at = NOW()
		WHERE id = $1
	`, jobID, message)

	return err
}

func (store *PostgresStore) LoadInput(ctx context.Context, semesterID int64) (Input, error) {
	loads, err := store.loadTeachingLoads(ctx, semesterID)
	if err != nil {
		return Input{}, err
	}

	rooms, err := store.loadRooms(ctx)
	if err != nil {
		return Input{}, err
	}

	slots, err := store.loadTimeSlots(ctx)
	if err != nil {
		return Input{}, err
	}

	assignments, err := store.loadTeacherSubjectAssignments(ctx)
	if err != nil {
		return Input{}, err
	}

	unavailable, err := store.loadTeacherUnavailability(ctx)
	if err != nil {
		return Input{}, err
	}

	return Input{TeachingLoads: loads, Rooms: rooms, TimeSlots: slots, Assignments: assignments, Unavailable: unavailable}, nil
}

func (store *PostgresStore) CreateDraftSchedule(ctx context.Context, message JobMessage, entries []CandidateEntry) (int64, error) {
	semester, err := store.loadSemester(ctx, message.SemesterID)
	if err != nil {
		return 0, err
	}

	tx, err := store.db.BeginTx(ctx, nil)
	if err != nil {
		return 0, fmt.Errorf("begin transaction: %w", err)
	}
	defer tx.Rollback()

	var scheduleID int64
	if err := tx.QueryRowContext(ctx, `
		INSERT INTO schedules (semester_id, status, valid_from, valid_to, created_by, created_at)
		VALUES ($1, 'draft', $2, $3, $4, NOW())
		RETURNING id
	`, semester.ID, semester.StartsAt, semester.EndsAt, message.RequestedByAdminID).Scan(&scheduleID); err != nil {
		return 0, fmt.Errorf("insert generated schedule: %w", err)
	}

	for _, entry := range entries {
		var entryID int64
		if err := tx.QueryRowContext(ctx, `
			INSERT INTO schedule_entries (schedule_id, subject_id, teacher_id, lesson_type, room_id, time_slot_id, day_of_week, week_parity)
			VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
			RETURNING id
		`, scheduleID, entry.SubjectID, entry.TeacherID, entry.LessonType, entry.RoomID, entry.TimeSlotID, entry.DayOfWeek, entry.WeekParity).Scan(&entryID); err != nil {
			return 0, fmt.Errorf("insert schedule entry: %w", err)
		}

		if _, err := tx.ExecContext(ctx, `
			INSERT INTO schedule_entry_groups (schedule_entry_id, group_id)
			VALUES ($1, $2)
		`, entryID, entry.GroupID); err != nil {
			return 0, fmt.Errorf("insert schedule entry group: %w", err)
		}

		if _, err := tx.ExecContext(ctx, `
			INSERT INTO schedule_entry_teaching_loads (schedule_entry_id, teaching_load_id)
			VALUES ($1, $2)
		`, entryID, entry.TeachingLoadID); err != nil {
			return 0, fmt.Errorf("insert schedule entry teaching load: %w", err)
		}
	}

	if err := tx.Commit(); err != nil {
		return 0, fmt.Errorf("commit generated schedule: %w", err)
	}

	return scheduleID, nil
}

func (store *PostgresStore) loadSemester(ctx context.Context, semesterID int64) (Semester, error) {
	var semester Semester
	if err := store.db.QueryRowContext(ctx, `
		SELECT id, starts_at::text, ends_at::text
		FROM semesters
		WHERE id = $1
	`, semesterID).Scan(&semester.ID, &semester.StartsAt, &semester.EndsAt); err != nil {
		return Semester{}, fmt.Errorf("load semester: %w", err)
	}

	return semester, nil
}

func (store *PostgresStore) loadTeachingLoads(ctx context.Context, semesterID int64) ([]TeachingLoad, error) {
	rows, err := store.db.QueryContext(ctx, `
		SELECT tl.id, tl.group_id, tl.subject_id, tl.teacher_id, tl.lesson_type, tl.required_lesson_count, g.student_count
		FROM teaching_loads tl
		INNER JOIN groups g ON g.id = tl.group_id
		WHERE tl.semester_id = $1 AND tl.deleted_at IS NULL
		ORDER BY tl.id ASC
	`, semesterID)
	if err != nil {
		return nil, fmt.Errorf("load teaching loads: %w", err)
	}
	defer rows.Close()

	loads := make([]TeachingLoad, 0)
	for rows.Next() {
		var load TeachingLoad
		if err := rows.Scan(&load.ID, &load.GroupID, &load.SubjectID, &load.TeacherID, &load.LessonType, &load.RequiredLessonCount, &load.StudentCount); err != nil {
			return nil, fmt.Errorf("scan teaching load: %w", err)
		}

		loads = append(loads, load)
	}

	return loads, rows.Err()
}

func (store *PostgresStore) loadRooms(ctx context.Context) ([]Room, error) {
	rows, err := store.db.QueryContext(ctx, `
		SELECT id, capacity
		FROM rooms
		ORDER BY capacity ASC, id ASC
	`)
	if err != nil {
		return nil, fmt.Errorf("load rooms: %w", err)
	}
	defer rows.Close()

	rooms := make([]Room, 0)
	for rows.Next() {
		var room Room
		if err := rows.Scan(&room.ID, &room.Capacity); err != nil {
			return nil, fmt.Errorf("scan room: %w", err)
		}

		rooms = append(rooms, room)
	}

	return rooms, rows.Err()
}

func (store *PostgresStore) loadTimeSlots(ctx context.Context) ([]TimeSlot, error) {
	rows, err := store.db.QueryContext(ctx, `
		SELECT id, to_char(starts_at, 'HH24:MI:SS'), to_char(ends_at, 'HH24:MI:SS')
		FROM time_slots
		ORDER BY number ASC, id ASC
	`)
	if err != nil {
		return nil, fmt.Errorf("load time slots: %w", err)
	}
	defer rows.Close()

	slots := make([]TimeSlot, 0)
	for rows.Next() {
		var slot TimeSlot
		if err := rows.Scan(&slot.ID, &slot.StartsAt, &slot.EndsAt); err != nil {
			return nil, fmt.Errorf("scan time slot: %w", err)
		}

		slots = append(slots, slot)
	}

	return slots, rows.Err()
}

func (store *PostgresStore) loadTeacherSubjectAssignments(ctx context.Context) ([]validation.TeacherSubject, error) {
	rows, err := store.db.QueryContext(ctx, `
		SELECT teacher_id, subject_id
		FROM teacher_subjects
		ORDER BY teacher_id ASC, subject_id ASC
	`)
	if err != nil {
		return nil, fmt.Errorf("load teacher subject assignments: %w", err)
	}
	defer rows.Close()

	assignments := make([]validation.TeacherSubject, 0)
	for rows.Next() {
		var assignment validation.TeacherSubject
		if err := rows.Scan(&assignment.TeacherID, &assignment.SubjectID); err != nil {
			return nil, fmt.Errorf("scan teacher subject assignment: %w", err)
		}

		assignments = append(assignments, assignment)
	}

	return assignments, rows.Err()
}

func (store *PostgresStore) loadTeacherUnavailability(ctx context.Context) ([]validation.TeacherUnavailability, error) {
	rows, err := store.db.QueryContext(ctx, `
		SELECT teacher_id, day_of_week, to_char(unavailable_from, 'HH24:MI:SS'), to_char(unavailable_to, 'HH24:MI:SS')
		FROM teacher_unavailability
		ORDER BY teacher_id ASC, day_of_week ASC
	`)
	if err != nil {
		return nil, fmt.Errorf("load teacher unavailability: %w", err)
	}
	defer rows.Close()

	unavailable := make([]validation.TeacherUnavailability, 0)
	for rows.Next() {
		var rule validation.TeacherUnavailability
		if err := rows.Scan(&rule.TeacherID, &rule.DayOfWeek, &rule.UnavailableFrom, &rule.UnavailableTo); err != nil {
			return nil, fmt.Errorf("scan teacher unavailability: %w", err)
		}

		unavailable = append(unavailable, rule)
	}

	return unavailable, rows.Err()
}
