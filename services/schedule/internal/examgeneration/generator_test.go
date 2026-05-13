package examgeneration

import "testing"

func TestGeneratorCreatesConsultationAndExamPair(t *testing.T) {
	entries, score, status, err := NewGenerator().Generate(validInput())
	if err != nil {
		t.Fatalf("Generate() error = %v", err)
	}

	if len(entries) != 2 {
		t.Fatalf("Generate() entries count = %d, want 2", len(entries))
	}
	if entries[0].Type != entryTypeConsultation {
		t.Fatalf("first entry type = %d, want consultation", entries[0].Type)
	}
	if entries[1].Type != entryTypeExam {
		t.Fatalf("second entry type = %d, want exam", entries[1].Type)
	}
	if entries[0].EntryDate != "2026-12-17" || entries[1].EntryDate != "2026-12-18" {
		t.Fatalf("entry dates = %s/%s, want 2026-12-17/2026-12-18", entries[0].EntryDate, entries[1].EntryDate)
	}
	if score < minimumQualityScore || status != "acceptable" {
		t.Fatalf("quality = %d/%s, want acceptable score", score, status)
	}
}

func TestGeneratorRejectsInsufficientRoomCapacity(t *testing.T) {
	input := validInput()
	input.Rooms = []Room{{ID: 1, Capacity: 10}}

	_, _, _, err := NewGenerator().Generate(input)
	if err == nil {
		t.Fatal("Generate() error = nil, want capacity failure")
	}
}

func TestGeneratorEnforcesMinimumDaysBetweenGroupExams(t *testing.T) {
	input := validInput()
	input.Semester.StartsAt = "2026-12-28"
	input.Semester.EndsAt = "2026-12-30"
	input.MinimumDaysBetweenExams = 3
	input.Demands = append(input.Demands, Demand{
		SubjectID:    2,
		TeacherID:    2,
		GroupIDs:     []int64{1},
		StudentCount: 24,
	})
	input.TeacherSubjectAssignments[teacherSubjectKey{TeacherID: 2, SubjectID: 2}] = true

	_, _, _, err := NewGenerator().Generate(input)
	if err == nil {
		t.Fatal("Generate() error = nil, want interval failure")
	}
}

func validInput() Input {
	return Input{
		Semester: Semester{
			ID:       1,
			StartsAt: "2026-09-01",
			EndsAt:   "2026-12-31",
		},
		Demands: []Demand{
			{
				SubjectID:    1,
				TeacherID:    1,
				GroupIDs:     []int64{1},
				StudentCount: 24,
			},
		},
		Rooms: []Room{
			{ID: 1, Capacity: 30},
		},
		TimeSlots: []TimeSlot{
			{ID: 1, StartsAt: "09:00:00"},
		},
		TeacherSubjectAssignments: map[teacherSubjectKey]bool{
			{TeacherID: 1, SubjectID: 1}: true,
		},
		ConsultationDaysBefore:  1,
		MinimumDaysBetweenExams: 1,
	}
}
