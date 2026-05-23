package examgeneration

import (
	"errors"
	"fmt"
	"sort"
	"time"
)

const (
	minimumQualityScore   = 80
	entryTypeConsultation = 1
	entryTypeExam         = 2
	dateLayout            = "2006-01-02"
)

type Generator struct{}

func NewGenerator() Generator {
	return Generator{}
}

func (generator Generator) Generate(input Input) ([]CandidateEntry, int, string, error) {
	if len(input.Demands) == 0 {
		return nil, 0, "failed", errors.New("semester has no exam demands")
	}
	if len(input.Rooms) == 0 {
		return nil, 0, "failed", errors.New("no rooms are available")
	}
	if len(input.TimeSlots) == 0 {
		return nil, 0, "failed", errors.New("no time slots are available")
	}

	examDates, err := examDates(input.Semester, input.ConsultationDaysBefore)
	if err != nil {
		return nil, 0, "failed", err
	}

	demands := cloneDemands(input.Demands)
	sort.Slice(demands, func(left int, right int) bool {
		if demands[left].StudentCount == demands[right].StudentCount {
			return demands[left].SubjectID < demands[right].SubjectID
		}

		return demands[left].StudentCount > demands[right].StudentCount
	})

	entries := make([]CandidateEntry, 0, len(demands)*2)
	for _, demand := range demands {
		if !input.TeacherSubjectAssignments[teacherSubjectKey{TeacherID: demand.TeacherID, SubjectID: demand.SubjectID}] {
			return nil, 0, "failed", fmt.Errorf("teacher %d is not assigned to subject %d", demand.TeacherID, demand.SubjectID)
		}

		pair, ok := generator.place(demand, examDates, entries, input)
		if !ok {
			return nil, 0, "failed", fmt.Errorf("cannot place exam for subject %d teacher %d", demand.SubjectID, demand.TeacherID)
		}

		entries = append(entries, pair...)
	}

	score := qualityScore(entries)
	if score < minimumQualityScore {
		return entries, score, "low_quality", fmt.Errorf("generated exam schedule quality score %d is below minimum %d", score, minimumQualityScore)
	}

	status := "acceptable"

	return entries, score, status, nil
}

func (generator Generator) place(demand Demand, dates []time.Time, entries []CandidateEntry, input Input) ([]CandidateEntry, bool) {
	for _, examDate := range dates {
		consultationDate := examDate.AddDate(0, 0, -input.ConsultationDaysBefore)
		for _, slot := range input.TimeSlots {
			for _, room := range input.Rooms {
				if room.Capacity < demand.StudentCount {
					continue
				}

				consultation := CandidateEntry{
					Type:         entryTypeConsultation,
					SubjectID:    demand.SubjectID,
					TeacherID:    demand.TeacherID,
					RoomID:       room.ID,
					RoomCapacity: room.Capacity,
					EntryDate:    consultationDate.Format(dateLayout),
					StartsAt:     slot.StartsAt,
					GroupIDs:     demand.GroupIDs,
					StudentCount: demand.StudentCount,
				}
				exam := consultation
				exam.Type = entryTypeExam
				exam.EntryDate = examDate.Format(dateLayout)

				if conflicts(consultation, entries) || conflicts(exam, entries) || violatesExamInterval(exam, entries, input.MinimumDaysBetweenExams) {
					continue
				}

				return []CandidateEntry{consultation, exam}, true
			}
		}
	}

	return nil, false
}

func conflicts(candidate CandidateEntry, entries []CandidateEntry) bool {
	for _, entry := range entries {
		if candidate.EntryDate != entry.EntryDate || candidate.StartsAt != entry.StartsAt {
			continue
		}
		if candidate.TeacherID == entry.TeacherID || candidate.RoomID == entry.RoomID || groupsOverlap(candidate.GroupIDs, entry.GroupIDs) {
			return true
		}
	}

	return false
}

func violatesExamInterval(candidate CandidateEntry, entries []CandidateEntry, minimumDays int) bool {
	if candidate.Type != entryTypeExam {
		return false
	}

	candidateDate, err := time.Parse(dateLayout, candidate.EntryDate)
	if err != nil {
		return true
	}

	for _, entry := range entries {
		if entry.Type != entryTypeExam || !groupsOverlap(candidate.GroupIDs, entry.GroupIDs) {
			continue
		}

		entryDate, err := time.Parse(dateLayout, entry.EntryDate)
		if err != nil {
			return true
		}

		days := int(candidateDate.Sub(entryDate).Hours() / 24)
		if days < 0 {
			days *= -1
		}
		if days < minimumDays {
			return true
		}
	}

	return false
}

func examDates(semester Semester, consultationDaysBefore int) ([]time.Time, error) {
	start, err := time.Parse(dateLayout, semester.StartsAt)
	if err != nil {
		return nil, fmt.Errorf("parse semester start date: %w", err)
	}
	end, err := time.Parse(dateLayout, semester.EndsAt)
	if err != nil {
		return nil, fmt.Errorf("parse semester end date: %w", err)
	}

	windowStart := end.AddDate(0, 0, -13)
	if windowStart.Before(start) {
		windowStart = start
	}

	dates := make([]time.Time, 0)
	for day := windowStart; !day.After(end); day = day.AddDate(0, 0, 1) {
		consultationDate := day.AddDate(0, 0, -consultationDaysBefore)
		if consultationDate.Before(start) || isWeekend(day) || isWeekend(consultationDate) {
			continue
		}
		dates = append(dates, day)
	}

	if len(dates) == 0 {
		return nil, errors.New("semester has no valid exam dates")
	}

	return dates, nil
}

func qualityScore(entries []CandidateEntry) int {
	if len(entries) == 0 {
		return 0
	}

	examsByDate := make(map[string]int)
	for _, entry := range entries {
		if entry.Type == entryTypeExam {
			examsByDate[entry.EntryDate]++
		}
	}

	score := 100
	for _, count := range examsByDate {
		if count > 3 {
			score -= (count - 3) * 5
		}
	}
	if score < 0 {
		return 0
	}

	return score
}

func groupsOverlap(left []int64, right []int64) bool {
	for _, leftID := range left {
		for _, rightID := range right {
			if leftID == rightID {
				return true
			}
		}
	}

	return false
}

func isWeekend(day time.Time) bool {
	return day.Weekday() == time.Saturday || day.Weekday() == time.Sunday
}

func cloneDemands(demands []Demand) []Demand {
	result := make([]Demand, len(demands))
	copy(result, demands)

	return result
}
