package generation

import (
	"errors"
	"fmt"

	"github.com/vkarchevskyi/university-schedule/services/schedule/internal/validation"
)

const minimumQualityScore = 80

type Input struct {
	TeachingLoads []TeachingLoad
	Rooms         []Room
	TimeSlots     []TimeSlot
	Assignments   []validation.TeacherSubject
	Unavailable   []validation.TeacherUnavailability
}

type Generator struct {
	validator validation.Validator
}

func NewGenerator(validator validation.Validator) Generator {
	return Generator{validator: validator}
}

func (generator Generator) Generate(input Input) ([]CandidateEntry, int, string, error) {
	if len(input.TeachingLoads) == 0 {
		return nil, 0, "failed", errors.New("semester has no active teaching loads")
	}
	if len(input.Rooms) == 0 {
		return nil, 0, "failed", errors.New("no rooms are available")
	}
	if len(input.TimeSlots) == 0 {
		return nil, 0, "failed", errors.New("no time slots are available")
	}

	entries := make([]CandidateEntry, 0)

	for _, load := range input.TeachingLoads {
		remaining := load.RequiredLessonCount
		for remaining > 0 {
			weekParity := 1
			contribution := 1
			if remaining >= 2 {
				weekParity = 3
				contribution = 2
			}

			entry, ok := generator.place(load, weekParity, entries, input)
			if !ok {
				return nil, 0, "failed", fmt.Errorf("cannot place teaching load %d", load.ID)
			}

			entries = append(entries, entry)
			remaining -= contribution
		}
	}

	entries = generator.optimize(entries, input)
	schedule := validation.Schedule{
		Entries:                    validationEntries(entries),
		TeachingLoads:              validationTeachingLoads(input.TeachingLoads),
		TeacherSubjectAssignments:  input.Assignments,
		TeacherUnavailabilityRules: input.Unavailable,
	}
	result := generator.validator.Validate(schedule)
	if !result.Valid {
		return entries, 0, "failed", fmt.Errorf("generated schedule has %d hard conflicts", len(result.Conflicts))
	}

	score := qualityScore(entries)
	status := "acceptable"
	if score < minimumQualityScore {
		status = "low_quality"
	}

	return entries, score, status, nil
}

func (generator Generator) optimize(entries []CandidateEntry, input Input) []CandidateEntry {
	best := cloneEntries(entries)
	current := cloneEntries(entries)
	tabu := make(map[string]int)

	for iteration := 0; iteration < 25; iteration++ {
		candidate, move, ok := bestNeighbor(current, input, tabu, iteration)
		if !ok {
			break
		}

		current = candidate
		tabu[move] = iteration + 5

		if qualityScore(current) > qualityScore(best) {
			best = cloneEntries(current)
		}
	}

	return best
}

func bestNeighbor(entries []CandidateEntry, input Input, tabu map[string]int, iteration int) ([]CandidateEntry, string, bool) {
	bestScore := -1
	bestMove := ""
	var best []CandidateEntry

	for index, entry := range entries {
		for day := 1; day <= 5; day++ {
			for _, slot := range input.TimeSlots {
				for _, room := range input.Rooms {
					if room.Capacity < entry.StudentCount {
						continue
					}

					move := fmt.Sprintf("%d:%d:%d:%d", index, day, slot.ID, room.ID)
					if expiresAt, exists := tabu[move]; exists && expiresAt > iteration {
						continue
					}

					candidate := cloneEntries(entries)
					candidate[index].DayOfWeek = day
					candidate[index].TimeSlotID = slot.ID
					candidate[index].TimeSlotStartsAt = slot.StartsAt
					candidate[index].TimeSlotEndsAt = slot.EndsAt
					candidate[index].RoomID = room.ID
					candidate[index].RoomCapacity = room.Capacity

					if conflictsWithOthers(candidate[index], candidate, index) || violatesTeacherUnavailability(candidate[index], input.Unavailable) {
						continue
					}

					score := qualityScore(candidate)
					if score > bestScore {
						bestScore = score
						bestMove = move
						best = candidate
					}
				}
			}
		}
	}

	return best, bestMove, best != nil
}

func (generator Generator) place(load TeachingLoad, weekParity int, entries []CandidateEntry, input Input) (CandidateEntry, bool) {
	for day := 1; day <= 5; day++ {
		for _, slot := range input.TimeSlots {
			for _, room := range input.Rooms {
				if room.Capacity < load.StudentCount {
					continue
				}

				entry := CandidateEntry{
					TeachingLoadID:   load.ID,
					GroupID:          load.GroupID,
					SubjectID:        load.SubjectID,
					TeacherID:        load.TeacherID,
					LessonType:       load.LessonType,
					RoomID:           room.ID,
					RoomCapacity:     room.Capacity,
					TimeSlotID:       slot.ID,
					TimeSlotStartsAt: slot.StartsAt,
					TimeSlotEndsAt:   slot.EndsAt,
					DayOfWeek:        day,
					WeekParity:       weekParity,
					StudentCount:     load.StudentCount,
				}
				if !conflicts(entry, entries) && !violatesTeacherUnavailability(entry, input.Unavailable) {
					return entry, true
				}
			}
		}
	}

	return CandidateEntry{}, false
}

func conflicts(candidate CandidateEntry, entries []CandidateEntry) bool {
	for _, entry := range entries {
		if candidate.DayOfWeek != entry.DayOfWeek || candidate.TimeSlotID != entry.TimeSlotID || !weekParityOverlaps(candidate.WeekParity, entry.WeekParity) {
			continue
		}
		if candidate.TeacherID == entry.TeacherID || candidate.RoomID == entry.RoomID || candidate.GroupID == entry.GroupID {
			return true
		}
	}

	return false
}

func conflictsWithOthers(candidate CandidateEntry, entries []CandidateEntry, candidateIndex int) bool {
	for index, entry := range entries {
		if index == candidateIndex {
			continue
		}
		if candidate.DayOfWeek != entry.DayOfWeek || candidate.TimeSlotID != entry.TimeSlotID || !weekParityOverlaps(candidate.WeekParity, entry.WeekParity) {
			continue
		}
		if candidate.TeacherID == entry.TeacherID || candidate.RoomID == entry.RoomID || candidate.GroupID == entry.GroupID {
			return true
		}
	}

	return false
}

func violatesTeacherUnavailability(candidate CandidateEntry, rules []validation.TeacherUnavailability) bool {
	start, end, ok := validation.ParseRange(candidate.TimeSlotStartsAt, candidate.TimeSlotEndsAt)
	if !ok {
		return true
	}

	for _, rule := range rules {
		if rule.TeacherID != candidate.TeacherID || rule.DayOfWeek != candidate.DayOfWeek {
			continue
		}

		unavailableFrom, unavailableTo, ok := validation.ParseRange(rule.UnavailableFrom, rule.UnavailableTo)
		if !ok {
			return true
		}
		if validation.TimeRangesOverlap(start, end, unavailableFrom, unavailableTo) {
			return true
		}
	}

	return false
}

func cloneEntries(entries []CandidateEntry) []CandidateEntry {
	result := make([]CandidateEntry, len(entries))
	copy(result, entries)

	return result
}

func weekParityOverlaps(left int, right int) bool {
	return left == 3 || right == 3 || left == right
}

func qualityScore(entries []CandidateEntry) int {
	if len(entries) == 0 {
		return 0
	}

	lateEntries := 0
	for _, entry := range entries {
		if entry.TimeSlotID > 5 {
			lateEntries++
		}
	}

	score := 100 - (lateEntries * 5)
	if score < 0 {
		return 0
	}

	return score
}

func validationEntries(entries []CandidateEntry) []validation.ScheduleEntry {
	result := make([]validation.ScheduleEntry, 0, len(entries))
	for index, entry := range entries {
		result = append(result, validation.ScheduleEntry{
			ID:               int64(index + 1),
			SubjectID:        entry.SubjectID,
			TeacherID:        entry.TeacherID,
			LessonType:       lessonTypeName(entry.LessonType),
			RoomID:           entry.RoomID,
			RoomCapacity:     entry.RoomCapacity,
			TimeSlotID:       entry.TimeSlotID,
			TimeSlotStartsAt: entry.TimeSlotStartsAt,
			TimeSlotEndsAt:   entry.TimeSlotEndsAt,
			DayOfWeek:        entry.DayOfWeek,
			WeekParity:       weekParityName(entry.WeekParity),
			GroupIDs:         []int64{entry.GroupID},
			StudentCount:     entry.StudentCount,
			TeachingLoadIDs:  []int64{entry.TeachingLoadID},
		})
	}

	return result
}

func validationTeachingLoads(loads []TeachingLoad) []validation.TeachingLoad {
	result := make([]validation.TeachingLoad, 0, len(loads))
	for _, load := range loads {
		result = append(result, validation.TeachingLoad{
			ID:                  load.ID,
			GroupID:             load.GroupID,
			SubjectID:           load.SubjectID,
			TeacherID:           load.TeacherID,
			LessonType:          lessonTypeName(load.LessonType),
			RequiredLessonCount: load.RequiredLessonCount,
		})
	}

	return result
}

func lessonTypeName(value int) string {
	return validation.LessonTypeName(value)
}

func weekParityName(value int) string {
	return validation.WeekParityName(value)
}
