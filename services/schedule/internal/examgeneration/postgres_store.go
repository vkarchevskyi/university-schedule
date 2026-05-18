package examgeneration

import (
	"context"
	"database/sql"
	"encoding/json"
	"fmt"

	_ "github.com/jackc/pgx/v5/stdlib"
)

type PostgresStore struct {
	db                      *sql.DB
	consultationDaysBefore  int
	minimumDaysBetweenExams int
}

func NewPostgresStore(databaseURL string, consultationDaysBefore int, minimumDaysBetweenExams int) (*PostgresStore, error) {
	db, err := sql.Open("pgx", databaseURL)
	if err != nil {
		return nil, fmt.Errorf("open database: %w", err)
	}

	return &PostgresStore{
		db:                      db,
		consultationDaysBefore:  consultationDaysBefore,
		minimumDaysBetweenExams: minimumDaysBetweenExams,
	}, nil
}

func (store *PostgresStore) Close() error {
	return store.db.Close()
}

func (store *PostgresStore) MarkRunning(ctx context.Context, jobID string) error {
	_, err := store.db.ExecContext(ctx, `
		UPDATE exam_schedule_generation_jobs
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
		UPDATE exam_schedule_generation_jobs
		SET status = 'completed',
			generated_exam_schedule_id = $2,
			quality_score = $3,
			quality_status = $4,
			diagnostics = $5,
			finished_at = NOW()
		WHERE id = $1
	`, jobID, result.ExamScheduleID, result.QualityScore, result.QualityStatus, string(diagnostics))

	return err
}

func (store *PostgresStore) MarkFailed(ctx context.Context, jobID string, message string) error {
	_, err := store.db.ExecContext(ctx, `
		UPDATE exam_schedule_generation_jobs
		SET status = 'failed', error_message = $2, finished_at = NOW()
		WHERE id = $1
	`, jobID, message)

	return err
}

func (store *PostgresStore) LoadInput(ctx context.Context, semesterID int64) (Input, error) {
	semester, err := store.loadSemester(ctx, semesterID)
	if err != nil {
		return Input{}, err
	}

	demands, err := store.loadDemands(ctx, semesterID)
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

	return Input{
		Semester:                  semester,
		Demands:                   demands,
		Rooms:                     rooms,
		TimeSlots:                 slots,
		TeacherSubjectAssignments: assignments,
		ConsultationDaysBefore:    store.consultationDaysBefore,
		MinimumDaysBetweenExams:   store.minimumDaysBetweenExams,
	}, nil
}

func (store *PostgresStore) CreateDraftExamSchedule(ctx context.Context, message JobMessage, entries []CandidateEntry) (int64, error) {
	tx, err := store.db.BeginTx(ctx, nil)
	if err != nil {
		return 0, fmt.Errorf("begin transaction: %w", err)
	}
	defer tx.Rollback()

	var examScheduleID int64
	if err := tx.QueryRowContext(ctx, `
		INSERT INTO exam_schedules (semester_id, status, created_by, created_at)
		VALUES ($1, 1, $2, NOW())
		RETURNING id
	`, message.SemesterID, message.RequestedByUserID).Scan(&examScheduleID); err != nil {
		return 0, fmt.Errorf("insert generated exam schedule: %w", err)
	}

	for _, entry := range entries {
		var entryID int64
		if err := tx.QueryRowContext(ctx, `
			INSERT INTO exam_schedule_entries (exam_schedule_id, type, subject_id, teacher_id, room_id, entry_date, starts_at)
			VALUES ($1, $2, $3, $4, $5, $6, $7)
			RETURNING id
		`, examScheduleID, entry.Type, entry.SubjectID, entry.TeacherID, entry.RoomID, entry.EntryDate, entry.StartsAt).Scan(&entryID); err != nil {
			return 0, fmt.Errorf("insert exam schedule entry: %w", err)
		}

		for _, groupID := range entry.GroupIDs {
			if _, err := tx.ExecContext(ctx, `
				INSERT INTO exam_schedule_entry_groups (exam_schedule_entry_id, group_id)
				VALUES ($1, $2)
			`, entryID, groupID); err != nil {
				return 0, fmt.Errorf("insert exam schedule entry group: %w", err)
			}
		}
	}

	if err := tx.Commit(); err != nil {
		return 0, fmt.Errorf("commit generated exam schedule: %w", err)
	}

	return examScheduleID, nil
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

func (store *PostgresStore) loadDemands(ctx context.Context, semesterID int64) ([]Demand, error) {
	rows, err := store.db.QueryContext(ctx, `
		SELECT DISTINCT tl.subject_id, tl.teacher_id, tl.group_id, g.student_count
		FROM teaching_loads tl
		INNER JOIN groups g ON g.id = tl.group_id
		WHERE tl.semester_id = $1 AND tl.deleted_at IS NULL
		ORDER BY tl.subject_id ASC, tl.teacher_id ASC, tl.group_id ASC
	`, semesterID)
	if err != nil {
		return nil, fmt.Errorf("load exam demands: %w", err)
	}
	defer rows.Close()

	demands := make([]Demand, 0)
	for rows.Next() {
		var demand Demand
		var groupID int64
		if err := rows.Scan(&demand.SubjectID, &demand.TeacherID, &groupID, &demand.StudentCount); err != nil {
			return nil, fmt.Errorf("scan exam demand: %w", err)
		}

		demand.GroupIDs = []int64{groupID}
		demands = append(demands, demand)
	}

	return demands, rows.Err()
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
		SELECT id, to_char(starts_at, 'HH24:MI:SS')
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
		if err := rows.Scan(&slot.ID, &slot.StartsAt); err != nil {
			return nil, fmt.Errorf("scan time slot: %w", err)
		}

		slots = append(slots, slot)
	}

	return slots, rows.Err()
}

func (store *PostgresStore) loadTeacherSubjectAssignments(ctx context.Context) (map[teacherSubjectKey]bool, error) {
	rows, err := store.db.QueryContext(ctx, `
		SELECT teacher_id, subject_id
		FROM teacher_subjects
		ORDER BY teacher_id ASC, subject_id ASC
	`)
	if err != nil {
		return nil, fmt.Errorf("load teacher subject assignments: %w", err)
	}
	defer rows.Close()

	assignments := make(map[teacherSubjectKey]bool)
	for rows.Next() {
		var assignment teacherSubjectKey
		if err := rows.Scan(&assignment.TeacherID, &assignment.SubjectID); err != nil {
			return nil, fmt.Errorf("scan teacher subject assignment: %w", err)
		}

		assignments[assignment] = true
	}

	return assignments, rows.Err()
}
