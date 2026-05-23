package examgeneration

import (
	"errors"
	"testing"
)

func TestRequireAffectedReportsMissingExamJob(t *testing.T) {
	err := requireAffected(fakeResult(0), ErrJobNotFound)
	if !errors.Is(err, ErrJobNotFound) {
		t.Fatalf("requireAffected() error = %v, want %v", err, ErrJobNotFound)
	}
}

type fakeResult int64

func (result fakeResult) LastInsertId() (int64, error) {
	return 0, nil
}

func (result fakeResult) RowsAffected() (int64, error) {
	return int64(result), nil
}
