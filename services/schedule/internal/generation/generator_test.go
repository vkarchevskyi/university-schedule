package generation

import (
	"testing"

	"github.com/vkarchevskyi/university-schedule/services/schedule/internal/validation"
)

func TestGeneratorCreatesValidSchedule(t *testing.T) {
	generator := NewGenerator(validation.NewValidator())

	entries, score, status, err := generator.Generate(Input{
		TeachingLoads: []TeachingLoad{
			{ID: 1, GroupID: 1, SubjectID: 1, TeacherID: 1, LessonType: 2, RequiredLessonCount: 3, StudentCount: 20},
		},
		Rooms: []Room{{ID: 1, Capacity: 30}},
		TimeSlots: []TimeSlot{
			{ID: 1, StartsAt: "08:30:00", EndsAt: "09:50:00"},
			{ID: 2, StartsAt: "10:00:00", EndsAt: "11:20:00"},
		},
		Assignments: []validation.TeacherSubject{{TeacherID: 1, SubjectID: 1}},
	})
	if err != nil {
		t.Fatalf("Generate() error = %v", err)
	}
	if len(entries) != 2 {
		t.Fatalf("len(entries) = %d, want 2", len(entries))
	}
	if score < minimumQualityScore {
		t.Fatalf("score = %d, want at least %d", score, minimumQualityScore)
	}
	if status != "acceptable" {
		t.Fatalf("status = %q, want acceptable", status)
	}
}

func TestGeneratorFailsWhenRoomCapacityIsInsufficient(t *testing.T) {
	generator := NewGenerator(validation.NewValidator())

	_, _, _, err := generator.Generate(Input{
		TeachingLoads: []TeachingLoad{
			{ID: 1, GroupID: 1, SubjectID: 1, TeacherID: 1, LessonType: 2, RequiredLessonCount: 1, StudentCount: 40},
		},
		Rooms:       []Room{{ID: 1, Capacity: 30}},
		TimeSlots:   []TimeSlot{{ID: 1, StartsAt: "08:30:00", EndsAt: "09:50:00"}},
		Assignments: []validation.TeacherSubject{{TeacherID: 1, SubjectID: 1}},
	})
	if err == nil {
		t.Fatal("Generate() error = nil, want error")
	}
}

func TestGeneratorAvoidsTeacherUnavailability(t *testing.T) {
	generator := NewGenerator(validation.NewValidator())

	entries, _, _, err := generator.Generate(Input{
		TeachingLoads: []TeachingLoad{
			{ID: 1, GroupID: 1, SubjectID: 1, TeacherID: 1, LessonType: 2, RequiredLessonCount: 1, StudentCount: 20},
		},
		Rooms: []Room{{ID: 1, Capacity: 30}},
		TimeSlots: []TimeSlot{
			{ID: 1, StartsAt: "08:30:00", EndsAt: "09:50:00"},
			{ID: 2, StartsAt: "10:00:00", EndsAt: "11:20:00"},
		},
		Assignments: []validation.TeacherSubject{{TeacherID: 1, SubjectID: 1}},
		Unavailable: []validation.TeacherUnavailability{
			{TeacherID: 1, DayOfWeek: 1, UnavailableFrom: "08:00:00", UnavailableTo: "12:00:00"},
		},
	})
	if err != nil {
		t.Fatalf("Generate() error = %v", err)
	}
	if len(entries) != 1 {
		t.Fatalf("len(entries) = %d, want 1", len(entries))
	}
	if entries[0].DayOfWeek == 1 {
		t.Fatalf("entry day = %d, want generator to avoid unavailable day", entries[0].DayOfWeek)
	}
}

func TestGeneratorFailsLowQualitySchedule(t *testing.T) {
	generator := NewGenerator(validation.NewValidator())

	_, score, status, err := generator.Generate(Input{
		TeachingLoads: []TeachingLoad{
			{ID: 1, GroupID: 1, SubjectID: 1, TeacherID: 1, LessonType: 2, RequiredLessonCount: 10, StudentCount: 20},
		},
		Rooms: []Room{{ID: 1, Capacity: 30}},
		TimeSlots: []TimeSlot{
			{ID: 6, StartsAt: "18:00:00", EndsAt: "19:20:00"},
		},
		Assignments: []validation.TeacherSubject{{TeacherID: 1, SubjectID: 1}},
	})
	if err == nil {
		t.Fatal("Generate() error = nil, want low quality error")
	}
	if status != "low_quality" {
		t.Fatalf("status = %q, want low_quality", status)
	}
	if score >= minimumQualityScore {
		t.Fatalf("score = %d, want below %d", score, minimumQualityScore)
	}
}
