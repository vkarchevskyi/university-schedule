package validation

import "testing"

func TestValidatorDetectsHardConflicts(t *testing.T) {
	validator := NewValidator()

	result := validator.Validate(Schedule{
		Entries: []ScheduleEntry{
			{
				ID:               1,
				SubjectID:        10,
				TeacherID:        20,
				LessonType:       "laboratory",
				RoomID:           30,
				RoomCapacity:     10,
				TimeSlotID:       40,
				TimeSlotStartsAt: "08:30:00",
				TimeSlotEndsAt:   "09:50:00",
				DayOfWeek:        1,
				WeekParity:       "odd",
				GroupIDs:         []int64{50},
				StudentCount:     12,
				TeachingLoadIDs:  []int64{60},
			},
			{
				ID:               2,
				SubjectID:        10,
				TeacherID:        20,
				LessonType:       "laboratory",
				RoomID:           30,
				RoomCapacity:     10,
				TimeSlotID:       40,
				TimeSlotStartsAt: "08:30:00",
				TimeSlotEndsAt:   "09:50:00",
				DayOfWeek:        1,
				WeekParity:       "both",
				GroupIDs:         []int64{50},
				StudentCount:     12,
				TeachingLoadIDs:  []int64{60},
			},
		},
		TeachingLoads: []TeachingLoad{
			{ID: 60, GroupID: 50, SubjectID: 10, TeacherID: 20, LessonType: "laboratory", RequiredLessonCount: 8},
		},
		TeacherSubjectAssignments: []TeacherSubject{},
		TeacherUnavailabilityRules: []TeacherUnavailability{
			{TeacherID: 20, DayOfWeek: 1, UnavailableFrom: "08:00:00", UnavailableTo: "09:00:00"},
		},
	})

	if result.Valid {
		t.Fatal("expected schedule to be invalid")
	}

	assertConflictType(t, result, "teacher_conflict")
	assertConflictType(t, result, "room_conflict")
	assertConflictType(t, result, "group_conflict")
	assertConflictType(t, result, "room_capacity_conflict")
	assertConflictType(t, result, "teacher_subject_mismatch")
	assertConflictType(t, result, "teacher_unavailability_conflict")
	assertConflictType(t, result, "teaching_load_missing")
}

func TestValidatorAcceptsCompleteSchedule(t *testing.T) {
	validator := NewValidator()

	result := validator.Validate(Schedule{
		Entries: []ScheduleEntry{
			{
				ID:               1,
				SubjectID:        10,
				TeacherID:        20,
				LessonType:       "laboratory",
				RoomID:           30,
				RoomCapacity:     20,
				TimeSlotID:       40,
				TimeSlotStartsAt: "08:30:00",
				TimeSlotEndsAt:   "09:50:00",
				DayOfWeek:        1,
				WeekParity:       "both",
				GroupIDs:         []int64{50},
				StudentCount:     12,
				TeachingLoadIDs:  []int64{60},
			},
		},
		TeachingLoads: []TeachingLoad{
			{ID: 60, GroupID: 50, SubjectID: 10, TeacherID: 20, LessonType: "laboratory", RequiredLessonCount: 2},
		},
		TeacherSubjectAssignments: []TeacherSubject{
			{TeacherID: 20, SubjectID: 10},
		},
	})

	if !result.Valid {
		t.Fatalf("expected schedule to be valid, got %#v", result.Conflicts)
	}
}

func TestValidatorRejectsWeekendEntries(t *testing.T) {
	validator := NewValidator()

	result := validator.Validate(Schedule{
		Entries: []ScheduleEntry{
			{
				ID:               1,
				SubjectID:        10,
				TeacherID:        20,
				LessonType:       "laboratory",
				RoomID:           30,
				RoomCapacity:     20,
				TimeSlotID:       40,
				TimeSlotStartsAt: "08:30:00",
				TimeSlotEndsAt:   "09:50:00",
				DayOfWeek:        6,
				WeekParity:       "both",
				GroupIDs:         []int64{50},
				StudentCount:     12,
				TeachingLoadIDs:  []int64{60},
			},
		},
		TeachingLoads: []TeachingLoad{
			{ID: 60, GroupID: 50, SubjectID: 10, TeacherID: 20, LessonType: "laboratory", RequiredLessonCount: 2},
		},
		TeacherSubjectAssignments: []TeacherSubject{
			{TeacherID: 20, SubjectID: 10},
		},
	})

	assertConflictType(t, result, "invalid_day_of_week")
}

func TestValidatorDetectsOverlappingTimeRanges(t *testing.T) {
	validator := NewValidator()

	result := validator.Validate(Schedule{
		Entries: []ScheduleEntry{
			{
				ID:               1,
				SubjectID:        10,
				TeacherID:        20,
				LessonType:       "laboratory",
				RoomID:           30,
				RoomCapacity:     20,
				TimeSlotID:       40,
				TimeSlotStartsAt: "08:30:00",
				TimeSlotEndsAt:   "09:50:00",
				DayOfWeek:        1,
				WeekParity:       "odd",
				GroupIDs:         []int64{50},
				StudentCount:     12,
				TeachingLoadIDs:  []int64{60},
			},
			{
				ID:               2,
				SubjectID:        10,
				TeacherID:        20,
				LessonType:       "laboratory",
				RoomID:           31,
				RoomCapacity:     20,
				TimeSlotID:       41,
				TimeSlotStartsAt: "09:00:00",
				TimeSlotEndsAt:   "10:20:00",
				DayOfWeek:        1,
				WeekParity:       "odd",
				GroupIDs:         []int64{51},
				StudentCount:     12,
				TeachingLoadIDs:  []int64{61},
			},
		},
		TeachingLoads: []TeachingLoad{
			{ID: 60, GroupID: 50, SubjectID: 10, TeacherID: 20, LessonType: "laboratory", RequiredLessonCount: 1},
			{ID: 61, GroupID: 51, SubjectID: 10, TeacherID: 20, LessonType: "laboratory", RequiredLessonCount: 1},
		},
		TeacherSubjectAssignments: []TeacherSubject{{TeacherID: 20, SubjectID: 10}},
	})

	assertConflictType(t, result, "teacher_conflict")
}

func TestValidatorDetectsTeachingLoadMismatch(t *testing.T) {
	validator := NewValidator()

	result := validator.Validate(Schedule{
		Entries: []ScheduleEntry{
			{
				ID:               1,
				SubjectID:        10,
				TeacherID:        20,
				LessonType:       "laboratory",
				RoomID:           30,
				RoomCapacity:     20,
				TimeSlotID:       40,
				TimeSlotStartsAt: "08:30:00",
				TimeSlotEndsAt:   "09:50:00",
				DayOfWeek:        1,
				WeekParity:       "odd",
				GroupIDs:         []int64{50},
				StudentCount:     12,
				TeachingLoadIDs:  []int64{60},
			},
		},
		TeachingLoads: []TeachingLoad{
			{ID: 60, GroupID: 50, SubjectID: 99, TeacherID: 20, LessonType: "laboratory", RequiredLessonCount: 1},
		},
		TeacherSubjectAssignments: []TeacherSubject{{TeacherID: 20, SubjectID: 10}},
	})

	assertConflictType(t, result, "teaching_load_mismatch")
}

func TestValidatorDetectsSchedulePeriodOutsideSemester(t *testing.T) {
	validator := NewValidator()

	result := validator.Validate(Schedule{
		SemesterStartsAt: "2026-09-01",
		SemesterEndsAt:   "2026-12-31",
		ValidFrom:        "2026-08-31",
		ValidTo:          "2026-12-31",
	})

	assertConflictType(t, result, "schedule_period_outside_semester")
}

func TestValuesSortsEntriesByID(t *testing.T) {
	entries := values(map[int64]ScheduleEntry{
		3: {ID: 3},
		1: {ID: 1},
		2: {ID: 2},
	})

	if len(entries) != 3 {
		t.Fatalf("len(entries) = %d, want 3", len(entries))
	}
	for index, entry := range entries {
		want := int64(index + 1)
		if entry.ID != want {
			t.Fatalf("entries[%d].ID = %d, want %d", index, entry.ID, want)
		}
	}
}

func assertConflictType(t *testing.T, result ValidationResult, conflictType string) {
	t.Helper()

	for _, conflict := range result.Conflicts {
		if conflict.Type == conflictType {
			return
		}
	}

	t.Fatalf("expected conflict type %q in %#v", conflictType, result.Conflicts)
}
