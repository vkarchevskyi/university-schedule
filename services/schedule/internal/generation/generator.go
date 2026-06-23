package generation

import (
	"errors"
	"fmt"

	"github.com/vkarchevskyi/university-schedule/services/schedule/internal/validation"
)

const (
	minimumQualityScore     = 80
	firstScheduleDay        = 1
	lastScheduleDay         = 5
	maxPlacementSearchNodes = 200000
)

type placementRequest struct {
	load       TeachingLoad
	weekParity int
}

type Input struct {
	TeachingLoads    []TeachingLoad
	AllTeachingLoads []TeachingLoad
	SeedEntries      []CandidateEntry
	Rooms            []Room
	TimeSlots        []TimeSlot
	Assignments      []validation.TeacherSubject
	Unavailable      []validation.TeacherUnavailability
}

type Generator struct {
	validator validation.Validator
}

func NewGenerator(validator validation.Validator) Generator {
	return Generator{validator: validator}
}

func (generator Generator) Generate(input Input) ([]CandidateEntry, int, string, error) {
	if len(input.TeachingLoads) == 0 && len(input.SeedEntries) == 0 {
		return nil, 0, "failed", errors.New("semester has no active teaching loads")
	}
	if len(input.Rooms) == 0 {
		return nil, 0, "failed", errors.New("no rooms are available")
	}
	if len(input.TimeSlots) == 0 {
		return nil, 0, "failed", errors.New("no time slots are available")
	}

	newEntries, failedLoadID, ok := generator.construct(input)
	if !ok {
		return nil, 0, "failed", fmt.Errorf("cannot place teaching load %d", failedLoadID)
	}

	newEntries = generator.optimize(newEntries, input)
	allEntries := append(cloneEntries(input.SeedEntries), newEntries...)
	validationLoads := input.AllTeachingLoads
	if len(validationLoads) == 0 {
		validationLoads = input.TeachingLoads
	}
	schedule := validation.Schedule{
		Entries:                    validationEntries(allEntries),
		TeachingLoads:              validationTeachingLoads(validationLoads),
		TeacherSubjectAssignments:  input.Assignments,
		TeacherUnavailabilityRules: input.Unavailable,
	}
	result := generator.validator.Validate(schedule)
	if !result.Valid {
		return allEntries, 0, "failed", fmt.Errorf("generated schedule has %d hard conflicts", len(result.Conflicts))
	}

	score := qualityScore(allEntries)
	if score < minimumQualityScore {
		return allEntries, score, "low_quality", fmt.Errorf("generated schedule quality score %d is below minimum %d", score, minimumQualityScore)
	}

	status := "acceptable"

	return newEntries, score, status, nil
}

func (generator Generator) construct(input Input) ([]CandidateEntry, int64, bool) {
	requests := placementRequests(input.TeachingLoads)
	if len(requests) == 0 {
		return []CandidateEntry{}, 0, true
	}

	for _, request := range requests {
		if len(feasibleCandidates(request, input.SeedEntries, input)) == 0 {
			return nil, request.load.ID, false
		}
	}

	entries := make([]CandidateEntry, 0, len(requests))
	placed := make([]bool, len(requests))
	searched := 0
	failedLoadID := int64(0)

	var backtrack func() bool
	backtrack = func() bool {
		if len(entries) == len(requests) {
			return true
		}
		searched++
		if searched > maxPlacementSearchNodes {
			return false
		}

		bestIndex := -1
		var bestCandidates []CandidateEntry
		for index, request := range requests {
			if placed[index] {
				continue
			}

			candidates := feasibleCandidates(request, append(input.SeedEntries, entries...), input)
			if len(candidates) == 0 {
				failedLoadID = request.load.ID
				return false
			}
			if bestIndex == -1 || len(candidates) < len(bestCandidates) {
				bestIndex = index
				bestCandidates = candidates
			}
		}

		placed[bestIndex] = true
		for _, candidate := range bestCandidates {
			entries = append(entries, candidate)
			if backtrack() {
				return true
			}
			entries = entries[:len(entries)-1]
		}
		placed[bestIndex] = false
		failedLoadID = requests[bestIndex].load.ID

		return false
	}

	if backtrack() {
		return entries, 0, true
	}
	if failedLoadID == 0 && len(requests) > 0 {
		failedLoadID = requests[0].load.ID
	}

	return nil, failedLoadID, false
}

func placementRequests(loads []TeachingLoad) []placementRequest {
	requests := make([]placementRequest, 0, len(loads))
	for _, load := range loads {
		remaining := load.RequiredLessonCount
		for remaining > 0 {
			weekParity := validation.WeekParityOdd
			contribution := 1
			if remaining >= 2 {
				weekParity = validation.WeekParityBoth
				contribution = 2
			}

			requests = append(requests, placementRequest{load: load, weekParity: weekParity})
			remaining -= contribution
		}
	}

	return requests
}

func feasibleCandidates(request placementRequest, entries []CandidateEntry, input Input) []CandidateEntry {
	candidates := make([]CandidateEntry, 0)
	for day := firstScheduleDay; day <= lastScheduleDay; day++ {
		for _, slot := range input.TimeSlots {
			for _, room := range input.Rooms {
				if room.Capacity < effectiveStudentCount(request.load.StudentCount, request.load.Subgroup) {
					continue
				}
				if !roomMatchesRequirement(room, request.load.RequiresComputerRoom) {
					continue
				}

				entry := CandidateEntry{
					TeachingLoadID:       request.load.ID,
					GroupID:              request.load.GroupID,
					SubjectID:            request.load.SubjectID,
					TeacherID:            request.load.TeacherID,
					LessonType:           request.load.LessonType,
					RoomID:               room.ID,
					RoomType:             room.Type,
					RoomCapacity:         room.Capacity,
					TimeSlotID:           slot.ID,
					TimeSlotNumber:       slot.Number,
					TimeSlotStartsAt:     slot.StartsAt,
					TimeSlotEndsAt:       slot.EndsAt,
					DayOfWeek:            day,
					WeekParity:           request.weekParity,
					StudentCount:         request.load.StudentCount,
					RequiresComputerRoom: request.load.RequiresComputerRoom,
					Subgroup:             request.load.Subgroup,
				}
				if !conflicts(entry, entries) && !violatesTeacherUnavailability(entry, input.Unavailable) {
					candidates = append(candidates, entry)
				}
			}
		}
	}

	return candidates
}

func (generator Generator) optimize(entries []CandidateEntry, input Input) []CandidateEntry {
	if len(entries) == 0 {
		return entries
	}

	best := cloneEntries(entries)
	current := cloneEntries(entries)
	tabu := make(map[string]int)

	for iteration := 0; iteration < 25; iteration++ {
		candidate, move, ok := bestNeighbor(current, input.SeedEntries, input, tabu, iteration)
		if !ok {
			break
		}

		current = candidate
		tabu[move] = iteration + 5

		if qualityScore(append(cloneEntries(input.SeedEntries), current...)) > qualityScore(append(cloneEntries(input.SeedEntries), best...)) {
			best = cloneEntries(current)
		}
	}

	return best
}

func bestNeighbor(entries []CandidateEntry, seedEntries []CandidateEntry, input Input, tabu map[string]int, iteration int) ([]CandidateEntry, string, bool) {
	bestScore := -1
	bestMove := ""
	var best []CandidateEntry

	for index, entry := range entries {
		for day := firstScheduleDay; day <= lastScheduleDay; day++ {
			for _, slot := range input.TimeSlots {
				for _, room := range input.Rooms {
					if room.Capacity < effectiveStudentCount(entry.StudentCount, entry.Subgroup) {
						continue
					}
					if !roomMatchesRequirement(room, entry.RequiresComputerRoom) {
						continue
					}

					move := fmt.Sprintf("%d:%d:%d:%d", index, day, slot.ID, room.ID)
					if expiresAt, exists := tabu[move]; exists && expiresAt > iteration {
						continue
					}

					candidate := cloneEntries(entries)
					candidate[index].DayOfWeek = day
					candidate[index].TimeSlotID = slot.ID
					candidate[index].TimeSlotNumber = slot.Number
					candidate[index].TimeSlotStartsAt = slot.StartsAt
					candidate[index].TimeSlotEndsAt = slot.EndsAt
					candidate[index].RoomID = room.ID
					candidate[index].RoomType = room.Type
					candidate[index].RoomCapacity = room.Capacity

					combined := append(cloneEntries(seedEntries), candidate...)
					if conflictsWithOthers(candidate[index], combined, index+len(seedEntries)) || violatesTeacherUnavailability(candidate[index], input.Unavailable) {
						continue
					}

					score := qualityScore(combined)
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

func roomMatchesRequirement(room Room, requiresComputerRoom bool) bool {
	return !requiresComputerRoom || room.Type == "computer"
}

func candidateGroupsOverlap(left CandidateEntry, right CandidateEntry) bool {
	return left.GroupID == right.GroupID && subgroupsOverlap(left.Subgroup, right.Subgroup)
}

func conflicts(candidate CandidateEntry, entries []CandidateEntry) bool {
	for _, entry := range entries {
		if candidate.DayOfWeek != entry.DayOfWeek || !candidateTimeRangesOverlap(candidate, entry) || !weekParityOverlaps(candidate.WeekParity, entry.WeekParity) {
			continue
		}

		if candidateGroupsOverlap(candidate, entry) {
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
		if candidate.DayOfWeek != entry.DayOfWeek || !candidateTimeRangesOverlap(candidate, entry) || !weekParityOverlaps(candidate.WeekParity, entry.WeekParity) {
			continue
		}

		if candidateGroupsOverlap(candidate, entry) {
			return true
		}
	}

	return false
}

func candidateTimeRangesOverlap(left CandidateEntry, right CandidateEntry) bool {
	leftStart, leftEnd, leftOK := validation.ParseRange(left.TimeSlotStartsAt, left.TimeSlotEndsAt)
	rightStart, rightEnd, rightOK := validation.ParseRange(right.TimeSlotStartsAt, right.TimeSlotEndsAt)
	if !leftOK || !rightOK {
		return true
	}

	return validation.TimeRangesOverlap(leftStart, leftEnd, rightStart, rightEnd)
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
	return validation.WeekParityOverlapsInt(left, right)
}

func subgroupsOverlap(left int, right int) bool {
	return validation.SubgroupsOverlap(left, right)
}

func effectiveStudentCount(studentCount int, subgroup int) int {
	if subgroup == 0 {
		return studentCount
	}

	return (studentCount + 1) / 2
}

func qualityScore(entries []CandidateEntry) int {
	if len(entries) == 0 {
		return 0
	}

	lateEntries := 0
	for _, entry := range entries {
		if entry.TimeSlotNumber > 5 {
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
			RoomType:         entry.RoomType,
			RoomCapacity:     entry.RoomCapacity,
			TimeSlotID:       entry.TimeSlotID,
			TimeSlotStartsAt: entry.TimeSlotStartsAt,
			TimeSlotEndsAt:   entry.TimeSlotEndsAt,
			DayOfWeek:        entry.DayOfWeek,
			WeekParity:       weekParityName(entry.WeekParity),
			GroupIDs:         []int64{entry.GroupID},
			StudentCount:     entry.StudentCount,
			TeachingLoadIDs:  []int64{entry.TeachingLoadID},
			Subgroup:         entry.Subgroup,
		})
	}

	return result
}

func validationTeachingLoads(loads []TeachingLoad) []validation.TeachingLoad {
	result := make([]validation.TeachingLoad, 0, len(loads))
	for _, load := range loads {
		result = append(result, validation.TeachingLoad{
			ID:                   load.ID,
			GroupID:              load.GroupID,
			SubjectID:            load.SubjectID,
			TeacherID:            load.TeacherID,
			LessonType:           lessonTypeName(load.LessonType),
			RequiredLessonCount:  load.RequiredLessonCount,
			RequiresComputerRoom: load.RequiresComputerRoom,
			Subgroup:             load.Subgroup,
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
