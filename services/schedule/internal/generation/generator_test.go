package generation

import (
	"testing"
	"time"

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
			{ID: 101, Number: 1, StartsAt: "08:30:00", EndsAt: "09:50:00"},
			{ID: 102, Number: 2, StartsAt: "10:00:00", EndsAt: "11:20:00"},
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
	for _, entry := range entries {
		if entry.DayOfWeek < firstScheduleDay || entry.DayOfWeek > lastScheduleDay {
			t.Fatalf("entry day = %d, want weekday", entry.DayOfWeek)
		}
	}
}

func TestGeneratorAllowsSameTeacherForDisjointGroupsAtSameTime(t *testing.T) {
	generator := NewGenerator(validation.NewValidator())

	entries, _, _, err := generator.Generate(Input{
		TeachingLoads: []TeachingLoad{
			{ID: 1, GroupID: 1, SubjectID: 1, TeacherID: 1, LessonType: 2, RequiredLessonCount: 1, StudentCount: 20},
			{ID: 2, GroupID: 2, SubjectID: 1, TeacherID: 1, LessonType: 2, RequiredLessonCount: 1, StudentCount: 20},
		},
		Rooms: []Room{{ID: 1, Capacity: 30}},
		TimeSlots: []TimeSlot{
			{ID: 1, StartsAt: "08:30:00", EndsAt: "09:50:00"},
			{ID: 2, StartsAt: "09:00:00", EndsAt: "10:20:00"},
			{ID: 3, StartsAt: "10:30:00", EndsAt: "11:50:00"},
		},
		Assignments: []validation.TeacherSubject{{TeacherID: 1, SubjectID: 1}},
	})
	if err != nil {
		t.Fatalf("Generate() error = %v", err)
	}
	if len(entries) != 2 {
		t.Fatalf("len(entries) = %d, want 2", len(entries))
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

func TestGeneratorUsesComputerRoomsForComputerRequirements(t *testing.T) {
	generator := NewGenerator(validation.NewValidator())

	entries, _, _, err := generator.Generate(Input{
		TeachingLoads: []TeachingLoad{
			{ID: 1, GroupID: 1, SubjectID: 1, TeacherID: 1, LessonType: 2, RequiredLessonCount: 1, RequiresComputerRoom: true, StudentCount: 20},
		},
		Rooms: []Room{
			{ID: 1, Type: "lecture", Capacity: 30},
			{ID: 2, Type: "computer", Capacity: 30},
		},
		TimeSlots:   []TimeSlot{{ID: 1, StartsAt: "08:30:00", EndsAt: "09:50:00"}},
		Assignments: []validation.TeacherSubject{{TeacherID: 1, SubjectID: 1}},
	})
	if err != nil {
		t.Fatalf("Generate() error = %v", err)
	}
	if len(entries) != 1 {
		t.Fatalf("len(entries) = %d, want 1", len(entries))
	}
	if entries[0].RoomID != 2 {
		t.Fatalf("entry room = %d, want computer room 2", entries[0].RoomID)
	}
}

func TestGeneratorBacktracksWhenFirstFeasiblePlacementBlocksConstrainedLoad(t *testing.T) {
	generator := NewGenerator(validation.NewValidator())

	entries, _, _, err := generator.Generate(Input{
		TeachingLoads: []TeachingLoad{
			{ID: 1, GroupID: 1, SubjectID: 1, TeacherID: 1, LessonType: 2, RequiredLessonCount: 1, StudentCount: 20},
			{ID: 2, GroupID: 2, SubjectID: 2, TeacherID: 2, LessonType: 2, RequiredLessonCount: 1, RequiresComputerRoom: true, StudentCount: 20},
		},
		Rooms: []Room{
			{ID: 1, Type: "computer", Capacity: 30},
			{ID: 2, Type: "lecture", Capacity: 30},
		},
		TimeSlots: []TimeSlot{{ID: 1, StartsAt: "08:30:00", EndsAt: "09:50:00"}},
		Assignments: []validation.TeacherSubject{
			{TeacherID: 1, SubjectID: 1},
			{TeacherID: 2, SubjectID: 2},
		},
		Unavailable: []validation.TeacherUnavailability{
			{TeacherID: 2, DayOfWeek: 2, UnavailableFrom: "08:00:00", UnavailableTo: "10:00:00"},
			{TeacherID: 2, DayOfWeek: 3, UnavailableFrom: "08:00:00", UnavailableTo: "10:00:00"},
			{TeacherID: 2, DayOfWeek: 4, UnavailableFrom: "08:00:00", UnavailableTo: "10:00:00"},
			{TeacherID: 2, DayOfWeek: 5, UnavailableFrom: "08:00:00", UnavailableTo: "10:00:00"},
		},
	})
	if err != nil {
		t.Fatalf("Generate() error = %v", err)
	}
	if len(entries) != 2 {
		t.Fatalf("len(entries) = %d, want 2", len(entries))
	}

	var constrained CandidateEntry
	for _, entry := range entries {
		if entry.TeachingLoadID == 2 {
			constrained = entry
			break
		}
	}
	if constrained.TeachingLoadID == 0 {
		t.Fatal("constrained teaching load was not scheduled")
	}
	if constrained.DayOfWeek != 1 || constrained.RoomID != 1 {
		t.Fatalf("constrained entry = day %d room %d, want day 1 computer room 1", constrained.DayOfWeek, constrained.RoomID)
	}
}

func TestGeneratorFailsWhenComputerRequirementHasNoComputerRoom(t *testing.T) {
	generator := NewGenerator(validation.NewValidator())

	_, _, _, err := generator.Generate(Input{
		TeachingLoads: []TeachingLoad{
			{ID: 1, GroupID: 1, SubjectID: 1, TeacherID: 1, LessonType: 2, RequiredLessonCount: 1, RequiresComputerRoom: true, StudentCount: 20},
		},
		Rooms:       []Room{{ID: 1, Type: "lecture", Capacity: 30}},
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

func TestGeneratorBacktracksWhenGreedyPlacementBlocksLaterLoad(t *testing.T) {
	generator := NewGenerator(validation.NewValidator())

	entries, _, _, err := generator.Generate(Input{
		TeachingLoads: []TeachingLoad{
			{ID: 1, GroupID: 1, SubjectID: 1, TeacherID: 1, LessonType: 2, RequiredLessonCount: 1, StudentCount: 20},
			{ID: 2, GroupID: 2, SubjectID: 2, TeacherID: 2, LessonType: 2, RequiredLessonCount: 1, StudentCount: 20},
		},
		Rooms:     []Room{{ID: 1, Capacity: 30}},
		TimeSlots: []TimeSlot{{ID: 1, StartsAt: "08:30:00", EndsAt: "09:50:00"}},
		Assignments: []validation.TeacherSubject{
			{TeacherID: 1, SubjectID: 1},
			{TeacherID: 2, SubjectID: 2},
		},
		Unavailable: []validation.TeacherUnavailability{
			{TeacherID: 2, DayOfWeek: 2, UnavailableFrom: "08:00:00", UnavailableTo: "10:00:00"},
			{TeacherID: 2, DayOfWeek: 3, UnavailableFrom: "08:00:00", UnavailableTo: "10:00:00"},
			{TeacherID: 2, DayOfWeek: 4, UnavailableFrom: "08:00:00", UnavailableTo: "10:00:00"},
			{TeacherID: 2, DayOfWeek: 5, UnavailableFrom: "08:00:00", UnavailableTo: "10:00:00"},
		},
	})
	if err != nil {
		t.Fatalf("Generate() error = %v", err)
	}
	if len(entries) != 2 {
		t.Fatalf("len(entries) = %d, want 2", len(entries))
	}

	var constrained CandidateEntry
	for _, entry := range entries {
		if entry.TeachingLoadID == 2 {
			constrained = entry
		}
	}
	if constrained.DayOfWeek != 1 {
		t.Fatalf("constrained load day = %d, want 1", constrained.DayOfWeek)
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
			{ID: 1, Number: 6, StartsAt: "18:00:00", EndsAt: "19:20:00"},
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

func TestGeneratorCompletesRemainingPlacementWithSeed(t *testing.T) {
	generator := NewGenerator(validation.NewValidator())
	seed := []CandidateEntry{{
		TeachingLoadID:   1,
		GroupID:          1,
		SubjectID:        1,
		TeacherID:        1,
		LessonType:       2,
		RoomID:           1,
		RoomType:         "lecture",
		RoomCapacity:     30,
		TimeSlotID:       1,
		TimeSlotNumber:   1,
		TimeSlotStartsAt: "08:30:00",
		TimeSlotEndsAt:   "09:50:00",
		DayOfWeek:        1,
		WeekParity:       1,
		StudentCount:     20,
	}}

	entries, _, status, err := generator.Generate(Input{
		TeachingLoads: []TeachingLoad{
			{ID: 1, GroupID: 1, SubjectID: 1, TeacherID: 1, LessonType: 2, RequiredLessonCount: 1, StudentCount: 20},
		},
		AllTeachingLoads: []TeachingLoad{
			{ID: 1, GroupID: 1, SubjectID: 1, TeacherID: 1, LessonType: 2, RequiredLessonCount: 2, StudentCount: 20},
		},
		SeedEntries: seed,
		Rooms:       []Room{{ID: 1, Capacity: 30}},
		TimeSlots: []TimeSlot{
			{ID: 1, Number: 1, StartsAt: "08:30:00", EndsAt: "09:50:00"},
			{ID: 2, Number: 2, StartsAt: "10:00:00", EndsAt: "11:20:00"},
		},
		Assignments: []validation.TeacherSubject{{TeacherID: 1, SubjectID: 1}},
	})
	if err != nil {
		t.Fatalf("Generate() error = %v", err)
	}
	if status != "acceptable" {
		t.Fatalf("status = %q, want acceptable", status)
	}
	if len(entries) != 1 {
		t.Fatalf("len(entries) = %d, want 1 new entry", len(entries))
	}
	if entries[0].DayOfWeek == 1 && entries[0].TimeSlotID == 1 {
		t.Fatalf("new entry overlaps seed placement: %#v", entries[0])
	}
}

func TestGeneratorFailsWhenSeedBlocksAllRemainingPlacements(t *testing.T) {
	generator := NewGenerator(validation.NewValidator())

	_, _, _, err := generator.Generate(Input{
		TeachingLoads: []TeachingLoad{
			{ID: 1, GroupID: 1, SubjectID: 1, TeacherID: 1, LessonType: 2, RequiredLessonCount: 1, StudentCount: 20},
		},
		AllTeachingLoads: []TeachingLoad{
			{ID: 1, GroupID: 1, SubjectID: 1, TeacherID: 1, LessonType: 2, RequiredLessonCount: 2, StudentCount: 20},
		},
		SeedEntries: []CandidateEntry{{
			TeachingLoadID:   1,
			GroupID:          1,
			SubjectID:        1,
			TeacherID:        1,
			LessonType:       2,
			RoomID:           1,
			RoomCapacity:     30,
			TimeSlotID:       1,
			TimeSlotNumber:   1,
			TimeSlotStartsAt: "08:30:00",
			TimeSlotEndsAt:   "09:50:00",
			DayOfWeek:        1,
			WeekParity:       1,
			StudentCount:     20,
		}},
		Rooms:     []Room{{ID: 1, Capacity: 30}},
		TimeSlots: []TimeSlot{{ID: 1, Number: 1, StartsAt: "08:30:00", EndsAt: "09:50:00"}},
		Assignments: []validation.TeacherSubject{{TeacherID: 1, SubjectID: 1}},
		Unavailable: []validation.TeacherUnavailability{
			{TeacherID: 1, DayOfWeek: 2, UnavailableFrom: "08:00:00", UnavailableTo: "12:00:00"},
			{TeacherID: 1, DayOfWeek: 3, UnavailableFrom: "08:00:00", UnavailableTo: "12:00:00"},
			{TeacherID: 1, DayOfWeek: 4, UnavailableFrom: "08:00:00", UnavailableTo: "12:00:00"},
			{TeacherID: 1, DayOfWeek: 5, UnavailableFrom: "08:00:00", UnavailableTo: "12:00:00"},
		},
	})
	if err == nil {
		t.Fatal("Generate() error = nil, want placement failure")
	}
}

func TestConflictsAllowDisjointGroupsAtSameTime(t *testing.T) {
	left := CandidateEntry{GroupID: 1, TeacherID: 10, RoomID: 20, DayOfWeek: 1, WeekParity: 1, Subgroup: 1}
	right := CandidateEntry{GroupID: 2, TeacherID: 10, RoomID: 20, DayOfWeek: 1, WeekParity: 1, Subgroup: 1}

	if conflicts(left, []CandidateEntry{right}) {
		t.Fatal("expected disjoint groups not to conflict")
	}
}

func TestConflictsDetectDisjointSubgroupsForSameGroup(t *testing.T) {
	left := CandidateEntry{GroupID: 1, DayOfWeek: 1, WeekParity: 1, Subgroup: 1}
	right := CandidateEntry{GroupID: 1, DayOfWeek: 1, WeekParity: 1, Subgroup: 2}

	if conflicts(left, []CandidateEntry{right}) {
		t.Fatal("expected disjoint subgroups not to conflict")
	}
}

func TestConflictsDetectSameGroupAtSameTime(t *testing.T) {
	left := CandidateEntry{GroupID: 1, DayOfWeek: 1, WeekParity: 1, Subgroup: 1}
	right := CandidateEntry{GroupID: 1, DayOfWeek: 1, WeekParity: 1, Subgroup: 1}

	if !conflicts(left, []CandidateEntry{right}) {
		t.Fatal("expected same group and subgroup to conflict")
	}
}

func mustParseRange(t *testing.T, entry CandidateEntry) (start, end time.Time) {
	t.Helper()

	start, end, ok := validation.ParseRange(entry.TimeSlotStartsAt, entry.TimeSlotEndsAt)
	if !ok {
		t.Fatalf("invalid entry time range: %#v", entry)
	}

	return start, end
}

func TestQualityScoreUsesTimeSlotNumberForLatePenalty(t *testing.T) {
	tests := []struct {
		name    string
		entries []CandidateEntry
		want    int
	}{
		{
			name:    "high id early slot is not penalized",
			entries: []CandidateEntry{{TimeSlotID: 60, TimeSlotNumber: 1}},
			want:    100,
		},
		{
			name:    "late slot number is penalized",
			entries: []CandidateEntry{{TimeSlotID: 1, TimeSlotNumber: 6}},
			want:    95,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			if got := qualityScore(tt.entries); got != tt.want {
				t.Fatalf("qualityScore() = %d, want %d", got, tt.want)
			}
		})
	}
}
