package generation

import (
	"context"
	"database/sql"
	"database/sql/driver"
	"errors"
	"io"
	"strings"
	"testing"
)

var generationFakeState *fakeGenerationState

func init() {
	sql.Register("generation_fake_tx", fakeGenerationDriver{})
}

func TestRequireAffectedReportsMissingJob(t *testing.T) {
	err := requireAffected(fakeResult(0), ErrJobNotFound)
	if !errors.Is(err, ErrJobNotFound) {
		t.Fatalf("requireAffected() error = %v, want %v", err, ErrJobNotFound)
	}
}

func TestCompleteJobWithDraftScheduleRollsBackWhenJobIsMissing(t *testing.T) {
	generationFakeState = &fakeGenerationState{}
	db, err := sql.Open("generation_fake_tx", "")
	if err != nil {
		t.Fatalf("open fake db: %v", err)
	}
	defer db.Close()

	store := &PostgresStore{db: db}
	_, err = store.CompleteJobWithDraftSchedule(context.Background(), JobMessage{
		JobID:             "missing",
		SemesterID:        1,
		RequestedByUserID: 2,
	}, []CandidateEntry{
		{
			TeachingLoadID: 1,
			GroupID:        1,
			SubjectID:      1,
			TeacherID:      1,
			LessonType:     2,
			RoomID:         1,
			TimeSlotID:     1,
			DayOfWeek:      1,
			WeekParity:     1,
		},
	}, Result{
		QualityScore:  100,
		QualityStatus: "acceptable",
		Diagnostics:   map[string]any{"generatedEntryCount": 1},
	})
	if !errors.Is(err, ErrJobNotFound) {
		t.Fatalf("CompleteJobWithDraftSchedule() error = %v, want %v", err, ErrJobNotFound)
	}
	if generationFakeState.committed {
		t.Fatal("transaction committed, want rollback")
	}
	if !generationFakeState.rolledBack {
		t.Fatal("transaction was not rolled back")
	}
	if generationFakeState.scheduleInserts != 1 {
		t.Fatalf("schedule inserts = %d, want 1 attempted insert before rollback", generationFakeState.scheduleInserts)
	}
}

type fakeGenerationState struct {
	committed       bool
	rolledBack      bool
	scheduleInserts int
}

type fakeGenerationDriver struct{}

func (fakeGenerationDriver) Open(string) (driver.Conn, error) {
	return fakeGenerationConn{state: generationFakeState}, nil
}

type fakeGenerationConn struct {
	state *fakeGenerationState
}

func (fakeGenerationConn) Prepare(string) (driver.Stmt, error) {
	return nil, errors.New("prepare is not supported")
}

func (fakeGenerationConn) Close() error {
	return nil
}

func (conn fakeGenerationConn) Begin() (driver.Tx, error) {
	return fakeGenerationTx{state: conn.state}, nil
}

func (conn fakeGenerationConn) BeginTx(context.Context, driver.TxOptions) (driver.Tx, error) {
	return fakeGenerationTx{state: conn.state}, nil
}

func (conn fakeGenerationConn) QueryContext(_ context.Context, query string, _ []driver.NamedValue) (driver.Rows, error) {
	switch {
	case strings.Contains(query, "FROM semesters"):
		return newFakeRows([]string{"id", "starts_at", "ends_at"}, [][]driver.Value{{int64(1), "2026-09-01", "2026-12-31"}}), nil
	case strings.Contains(query, "INSERT INTO schedules"):
		conn.state.scheduleInserts++
		return newFakeRows([]string{"id"}, [][]driver.Value{{int64(100)}}), nil
	case strings.Contains(query, "INSERT INTO schedule_entries"):
		return newFakeRows([]string{"id"}, [][]driver.Value{{int64(200)}}), nil
	default:
		return nil, errors.New("unexpected query: " + query)
	}
}

func (fakeGenerationConn) ExecContext(_ context.Context, query string, _ []driver.NamedValue) (driver.Result, error) {
	if strings.Contains(query, "UPDATE schedule_generation_jobs") {
		return fakeResult(0), nil
	}

	return fakeResult(1), nil
}

type fakeGenerationTx struct {
	state *fakeGenerationState
}

func (tx fakeGenerationTx) Commit() error {
	tx.state.committed = true
	return nil
}

func (tx fakeGenerationTx) Rollback() error {
	tx.state.rolledBack = true
	return nil
}

type fakeResult int64

func (result fakeResult) LastInsertId() (int64, error) {
	return 0, nil
}

func (result fakeResult) RowsAffected() (int64, error) {
	return int64(result), nil
}

type fakeRows struct {
	columns []string
	values  [][]driver.Value
	index   int
}

func newFakeRows(columns []string, values [][]driver.Value) *fakeRows {
	return &fakeRows{columns: columns, values: values}
}

func (rows *fakeRows) Columns() []string {
	return rows.columns
}

func (rows *fakeRows) Close() error {
	return nil
}

func (rows *fakeRows) Next(dest []driver.Value) error {
	if rows.index >= len(rows.values) {
		return io.EOF
	}

	copy(dest, rows.values[rows.index])
	rows.index++

	return nil
}
