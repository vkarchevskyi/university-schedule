package generation

import (
	"context"
	"database/sql"
	"encoding/json"
	"errors"
	"fmt"
	"time"

	_ "github.com/jackc/pgx/v5/stdlib"
	"github.com/vkarchevskyi/university-schedule/services/schedule/internal/validation"
)

var ErrJobNotFound = errors.New("schedule generation job not found")

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
	result, err := store.db.ExecContext(ctx, `
		UPDATE schedule_generation_jobs
		SET status = 'running',
			error_message = NULL,
			started_at = NOW(),
			finished_at = NULL
		WHERE id = $1
	`, jobID)
	if err != nil {
		return err
	}

	return requireAffected(result, ErrJobNotFound)
}

func (store *PostgresStore) MarkCompleted(ctx context.Context, jobID string, result Result) error {
	diagnostics, err := json.Marshal(result.Diagnostics)
	if err != nil {
		return fmt.Errorf("marshal diagnostics: %w", err)
	}

	update, err := store.db.ExecContext(ctx, `
		UPDATE schedule_generation_jobs
		SET status = 'completed',
			generated_schedule_id = $2,
			quality_score = $3,
			quality_status = $4,
			diagnostics = $5,
			error_message = NULL,
			finished_at = NOW()
		WHERE id = $1
	`, jobID, result.ScheduleID, result.QualityScore, result.QualityStatus, string(diagnostics))
	if err != nil {
		return err
	}

	return requireAffected(update, ErrJobNotFound)
}

func (store *PostgresStore) MarkFailed(ctx context.Context, jobID string, message string) error {
	result, err := store.db.ExecContext(ctx, `
		UPDATE schedule_generation_jobs
		SET status = 'failed',
			generated_schedule_id = NULL,
			quality_score = NULL,
			quality_status = NULL,
			diagnostics = NULL,
			error_message = $2,
			finished_at = NOW()
		WHERE id = $1
	`, jobID, message)
	if err != nil {
		return err
	}

	return requireAffected(result, ErrJobNotFound)
}

func (store *PostgresStore) LoadJob(ctx context.Context, jobID string) (JobResource, error) {
	var job JobResource
	var generatedScheduleID sql.NullInt64
	var qualityScore sql.NullInt64
	var qualityStatus sql.NullString
	var errorMessage sql.NullString
	var diagnostics []byte
	var createdAt time.Time
	var startedAt sql.NullTime
	var finishedAt sql.NullTime

	err := store.db.QueryRowContext(ctx, `
		SELECT id, semester_id, requested_by, status, generated_schedule_id, quality_score, quality_status, error_message, diagnostics, created_at, started_at, finished_at
		FROM schedule_generation_jobs
		WHERE id = $1
	`, jobID).Scan(
		&job.ID,
		&job.SemesterID,
		&job.RequestedBy,
		&job.Status,
		&generatedScheduleID,
		&qualityScore,
		&qualityStatus,
		&errorMessage,
		&diagnostics,
		&createdAt,
		&startedAt,
		&finishedAt,
	)
	if err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return JobResource{}, ErrJobNotFound
		}
		return JobResource{}, err
	}

	if generatedScheduleID.Valid {
		job.GeneratedScheduleID = &generatedScheduleID.Int64
	}
	if qualityScore.Valid {
		score := int(qualityScore.Int64)
		job.QualityScore = &score
	}
	if qualityStatus.Valid {
		job.QualityStatus = &qualityStatus.String
	}
	if errorMessage.Valid {
		job.ErrorMessage = &errorMessage.String
	}
	if diagnostics != nil {
		if err := json.Unmarshal(diagnostics, &job.Diagnostics); err != nil {
			return JobResource{}, fmt.Errorf("decode diagnostics: %w", err)
		}
	}
	job.CreatedAt = createdAt.Format(time.RFC3339)
	if startedAt.Valid {
		formatted := startedAt.Time.Format(time.RFC3339)
		job.StartedAt = &formatted
	}
	if finishedAt.Valid {
		formatted := finishedAt.Time.Format(time.RFC3339)
		job.FinishedAt = &formatted
	}

	return job, nil
}

func (store *PostgresStore) LoadInput(ctx context.Context, semesterID int64, baseScheduleID *int64) (Input, error) {
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

	allTeachingLoads := cloneTeachingLoads(loads)
	seedEntries := []CandidateEntry{}
	remainingLoads := loads

	if baseScheduleID != nil {
		seedEntries, err = store.loadSeedEntries(ctx, *baseScheduleID)
		if err != nil {
			return Input{}, err
		}

		remainingLoads = reduceTeachingLoads(loads, seedEntries)
	}

	return Input{
		TeachingLoads:    remainingLoads,
		AllTeachingLoads: allTeachingLoads,
		SeedEntries:      seedEntries,
		Rooms:            rooms,
		TimeSlots:        slots,
		Assignments:      assignments,
		Unavailable:      unavailable,
	}, nil
}

func (store *PostgresStore) CreateDraftSchedule(ctx context.Context, message JobMessage, entries []CandidateEntry) (int64, error) {
	tx, err := store.db.BeginTx(ctx, nil)
	if err != nil {
		return 0, fmt.Errorf("begin transaction: %w", err)
	}
	defer tx.Rollback()

	scheduleID, err := store.createDraftSchedule(ctx, tx, message, entries)
	if err != nil {
		return 0, err
	}

	if err := tx.Commit(); err != nil {
		return 0, fmt.Errorf("commit generated schedule: %w", err)
	}

	return scheduleID, nil
}

func (store *PostgresStore) CompleteJobWithDraftSchedule(ctx context.Context, message JobMessage, entries []CandidateEntry, result Result) (int64, error) {
	diagnostics, err := json.Marshal(result.Diagnostics)
	if err != nil {
		return 0, fmt.Errorf("marshal diagnostics: %w", err)
	}

	tx, err := store.db.BeginTx(ctx, nil)
	if err != nil {
		return 0, fmt.Errorf("begin transaction: %w", err)
	}
	defer tx.Rollback()

	var scheduleID int64
	if message.BaseScheduleID != nil {
		scheduleID = *message.BaseScheduleID
		if err := store.appendScheduleEntries(ctx, tx, scheduleID, entries); err != nil {
			return 0, err
		}
	} else {
		scheduleID, err = store.createDraftSchedule(ctx, tx, message, entries)
		if err != nil {
			return 0, err
		}
	}

	update, err := tx.ExecContext(ctx, `
		UPDATE schedule_generation_jobs
		SET status = 'completed',
			generated_schedule_id = $2,
			quality_score = $3,
			quality_status = $4,
			diagnostics = $5,
			error_message = NULL,
			finished_at = NOW()
		WHERE id = $1
	`, message.JobID, scheduleID, result.QualityScore, result.QualityStatus, string(diagnostics))
	if err != nil {
		return 0, err
	}
	if err := requireAffected(update, ErrJobNotFound); err != nil {
		return 0, err
	}

	if err := tx.Commit(); err != nil {
		return 0, fmt.Errorf("commit generated schedule: %w", err)
	}

	return scheduleID, nil
}

func (store *PostgresStore) createDraftSchedule(ctx context.Context, tx *sql.Tx, message JobMessage, entries []CandidateEntry) (int64, error) {
	var semester Semester
	if err := tx.QueryRowContext(ctx, `
		SELECT id, starts_at::text, ends_at::text
		FROM semesters
		WHERE id = $1
	`, message.SemesterID).Scan(&semester.ID, &semester.StartsAt, &semester.EndsAt); err != nil {
		return 0, fmt.Errorf("load semester: %w", err)
	}

	var scheduleID int64
	if err := tx.QueryRowContext(ctx, `
		INSERT INTO schedules (semester_id, status, valid_from, valid_to, created_by, created_at)
		VALUES ($1, 'draft', $2, $3, $4, NOW())
		RETURNING id
	`, semester.ID, semester.StartsAt, semester.EndsAt, message.RequestedByUserID).Scan(&scheduleID); err != nil {
		return 0, fmt.Errorf("insert generated schedule: %w", err)
	}

	for _, entry := range entries {
		if err := store.insertScheduleEntry(ctx, tx, scheduleID, entry); err != nil {
			return 0, err
		}
	}

	return scheduleID, nil
}

func (store *PostgresStore) appendScheduleEntries(ctx context.Context, tx *sql.Tx, scheduleID int64, entries []CandidateEntry) error {
	for _, entry := range entries {
		if err := store.insertScheduleEntry(ctx, tx, scheduleID, entry); err != nil {
			return err
		}
	}

	return nil
}

func (store *PostgresStore) insertScheduleEntry(ctx context.Context, tx *sql.Tx, scheduleID int64, entry CandidateEntry) error {
	var entryID int64
	if err := tx.QueryRowContext(ctx, `
		INSERT INTO schedule_entries (schedule_id, subject_id, teacher_id, lesson_type, room_id, time_slot_id, day_of_week, week_parity, subgroup)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8, NULLIF($9, 0))
		RETURNING id
	`, scheduleID, entry.SubjectID, entry.TeacherID, entry.LessonType, entry.RoomID, entry.TimeSlotID, entry.DayOfWeek, entry.WeekParity, entry.Subgroup).Scan(&entryID); err != nil {
		return fmt.Errorf("insert schedule entry: %w", err)
	}

	if _, err := tx.ExecContext(ctx, `
		INSERT INTO schedule_entry_groups (schedule_entry_id, group_id)
		VALUES ($1, $2)
	`, entryID, entry.GroupID); err != nil {
		return fmt.Errorf("insert schedule entry group: %w", err)
	}

	if _, err := tx.ExecContext(ctx, `
		INSERT INTO schedule_entry_teaching_loads (schedule_entry_id, teaching_load_id)
		VALUES ($1, $2)
	`, entryID, entry.TeachingLoadID); err != nil {
		return fmt.Errorf("insert schedule entry teaching load: %w", err)
	}

	return nil
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
		SELECT tl.id, tl.group_id, tl.subject_id, tl.teacher_id, tl.lesson_type, tl.required_lesson_count, tl.requires_computer_room, g.student_count, COALESCE(tl.subgroup, 0)
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
		if err := rows.Scan(&load.ID, &load.GroupID, &load.SubjectID, &load.TeacherID, &load.LessonType, &load.RequiredLessonCount, &load.RequiresComputerRoom, &load.StudentCount, &load.Subgroup); err != nil {
			return nil, fmt.Errorf("scan teaching load: %w", err)
		}

		loads = append(loads, load)
	}

	return loads, rows.Err()
}

func (store *PostgresStore) loadRooms(ctx context.Context) ([]Room, error) {
	rows, err := store.db.QueryContext(ctx, `
		SELECT id, type, capacity
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
		if err := rows.Scan(&room.ID, &room.Type, &room.Capacity); err != nil {
			return nil, fmt.Errorf("scan room: %w", err)
		}

		rooms = append(rooms, room)
	}

	return rooms, rows.Err()
}

func (store *PostgresStore) loadTimeSlots(ctx context.Context) ([]TimeSlot, error) {
	rows, err := store.db.QueryContext(ctx, `
		SELECT id, number, to_char(starts_at, 'HH24:MI:SS'), to_char(ends_at, 'HH24:MI:SS')
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
		if err := rows.Scan(&slot.ID, &slot.Number, &slot.StartsAt, &slot.EndsAt); err != nil {
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

func (store *PostgresStore) loadSeedEntries(ctx context.Context, scheduleID int64) ([]CandidateEntry, error) {
	rows, err := store.db.QueryContext(ctx, `
		SELECT
			setl.teaching_load_id,
			tl.group_id,
			se.subject_id,
			se.teacher_id,
			se.lesson_type,
			se.room_id,
			r.type,
			r.capacity,
			se.time_slot_id,
			ts.number,
			to_char(ts.starts_at, 'HH24:MI:SS'),
			to_char(ts.ends_at, 'HH24:MI:SS'),
			se.day_of_week,
			se.week_parity,
			g.student_count,
			tl.requires_computer_room,
			COALESCE(se.subgroup, 0)
		FROM schedule_entries se
		INNER JOIN schedule_entry_teaching_loads setl ON setl.schedule_entry_id = se.id
		INNER JOIN teaching_loads tl ON tl.id = setl.teaching_load_id
		INNER JOIN groups g ON g.id = tl.group_id
		INNER JOIN rooms r ON r.id = se.room_id
		INNER JOIN time_slots ts ON ts.id = se.time_slot_id
		INNER JOIN schedule_entry_groups seg ON seg.schedule_entry_id = se.id AND seg.group_id = tl.group_id
		WHERE se.schedule_id = $1
		ORDER BY se.id ASC, setl.teaching_load_id ASC
	`, scheduleID)
	if err != nil {
		return nil, fmt.Errorf("load seed schedule entries: %w", err)
	}
	defer rows.Close()

	entries := make([]CandidateEntry, 0)
	for rows.Next() {
		var entry CandidateEntry
		if err := rows.Scan(
			&entry.TeachingLoadID,
			&entry.GroupID,
			&entry.SubjectID,
			&entry.TeacherID,
			&entry.LessonType,
			&entry.RoomID,
			&entry.RoomType,
			&entry.RoomCapacity,
			&entry.TimeSlotID,
			&entry.TimeSlotNumber,
			&entry.TimeSlotStartsAt,
			&entry.TimeSlotEndsAt,
			&entry.DayOfWeek,
			&entry.WeekParity,
			&entry.StudentCount,
			&entry.RequiresComputerRoom,
			&entry.Subgroup,
		); err != nil {
			return nil, fmt.Errorf("scan seed schedule entry: %w", err)
		}

		entries = append(entries, entry)
	}

	return entries, rows.Err()
}

func cloneTeachingLoads(loads []TeachingLoad) []TeachingLoad {
	result := make([]TeachingLoad, len(loads))
	copy(result, loads)

	return result
}

func reduceTeachingLoads(loads []TeachingLoad, seedEntries []CandidateEntry) []TeachingLoad {
	scheduledCounts := make(map[int64]int)
	for _, entry := range seedEntries {
		scheduledCounts[entry.TeachingLoadID] += validation.LessonCountFromWeekParity(entry.WeekParity)
	}

	remainingLoads := make([]TeachingLoad, 0, len(loads))
	for _, load := range loads {
		remaining := load.RequiredLessonCount - scheduledCounts[load.ID]
		if remaining <= 0 {
			continue
		}

		load.RequiredLessonCount = remaining
		remainingLoads = append(remainingLoads, load)
	}

	return remainingLoads
}

func requireAffected(result sql.Result, notFound error) error {
	affected, err := result.RowsAffected()
	if err != nil {
		return fmt.Errorf("read affected rows: %w", err)
	}
	if affected == 0 {
		return notFound
	}

	return nil
}
