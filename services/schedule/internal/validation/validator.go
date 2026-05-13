package validation

import (
	"fmt"
	"slices"
	"time"
)

type ScheduleValidationRequest struct {
	ScheduleID int64     `json:"scheduleId"`
	Schedule   *Schedule `json:"schedule,omitempty"`
}

type Schedule struct {
	ID                         int64                   `json:"id"`
	Entries                    []ScheduleEntry         `json:"entries"`
	TeachingLoads              []TeachingLoad          `json:"teachingLoads"`
	TeacherSubjectAssignments  []TeacherSubject        `json:"teacherSubjectAssignments"`
	TeacherUnavailabilityRules []TeacherUnavailability `json:"teacherUnavailabilityRules"`
}

type ScheduleEntry struct {
	ID               int64   `json:"id"`
	SubjectID        int64   `json:"subjectId"`
	TeacherID        int64   `json:"teacherId"`
	LessonType       string  `json:"lessonType"`
	RoomID           int64   `json:"roomId"`
	RoomCapacity     int     `json:"roomCapacity"`
	TimeSlotID       int64   `json:"timeSlotId"`
	TimeSlotStartsAt string  `json:"timeSlotStartsAt"`
	TimeSlotEndsAt   string  `json:"timeSlotEndsAt"`
	DayOfWeek        int     `json:"dayOfWeek"`
	WeekParity       string  `json:"weekParity"`
	GroupIDs         []int64 `json:"groupIds"`
	StudentCount     int     `json:"studentCount"`
	TeachingLoadIDs  []int64 `json:"teachingLoadIds"`
}

type TeachingLoad struct {
	ID                  int64  `json:"id"`
	GroupID             int64  `json:"groupId"`
	SubjectID           int64  `json:"subjectId"`
	TeacherID           int64  `json:"teacherId"`
	LessonType          string `json:"lessonType"`
	RequiredLessonCount int    `json:"requiredLessonCount"`
}

type TeacherSubject struct {
	TeacherID int64 `json:"teacherId"`
	SubjectID int64 `json:"subjectId"`
}

type TeacherUnavailability struct {
	TeacherID       int64  `json:"teacherId"`
	DayOfWeek       int    `json:"dayOfWeek"`
	UnavailableFrom string `json:"unavailableFrom"`
	UnavailableTo   string `json:"unavailableTo"`
}

type ValidationResult struct {
	Valid     bool       `json:"valid"`
	Conflicts []Conflict `json:"conflicts"`
}

type Conflict struct {
	Type     string  `json:"type"`
	Message  string  `json:"message"`
	EntryIDs []int64 `json:"entryIds"`
}

type Validator struct{}

func NewValidator() Validator {
	return Validator{}
}

func (Validator) Validate(schedule Schedule) ValidationResult {
	conflicts := make([]Conflict, 0)
	conflicts = append(conflicts, validateEntryConflicts(schedule.Entries)...)
	conflicts = append(conflicts, validateCapacity(schedule.Entries)...)
	conflicts = append(conflicts, validateTeacherSubjects(schedule)...)
	conflicts = append(conflicts, validateTeacherUnavailability(schedule)...)
	conflicts = append(conflicts, validateTeachingLoads(schedule)...)

	return ValidationResult{
		Valid:     len(conflicts) == 0,
		Conflicts: conflicts,
	}
}

func validateEntryConflicts(entries []ScheduleEntry) []Conflict {
	conflicts := make([]Conflict, 0)

	for leftIndex, left := range entries {
		for _, right := range entries[leftIndex+1:] {
			if left.DayOfWeek != right.DayOfWeek || left.TimeSlotID != right.TimeSlotID || !weekParityOverlaps(left.WeekParity, right.WeekParity) {
				continue
			}

			entryIDs := []int64{left.ID, right.ID}

			if left.TeacherID == right.TeacherID {
				conflicts = append(conflicts, Conflict{"teacher_conflict", "Teacher is already assigned at this time.", entryIDs})
			}

			if left.RoomID == right.RoomID {
				conflicts = append(conflicts, Conflict{"room_conflict", "Room is already assigned at this time.", entryIDs})
			}

			if hasInt64Overlap(left.GroupIDs, right.GroupIDs) {
				conflicts = append(conflicts, Conflict{"group_conflict", "Group is already assigned at this time.", entryIDs})
			}
		}
	}

	return conflicts
}

func validateCapacity(entries []ScheduleEntry) []Conflict {
	conflicts := make([]Conflict, 0)

	for _, entry := range entries {
		if entry.StudentCount > entry.RoomCapacity {
			conflicts = append(conflicts, Conflict{
				Type:     "room_capacity_conflict",
				Message:  fmt.Sprintf("Room capacity is %d, but scheduled groups contain %d students.", entry.RoomCapacity, entry.StudentCount),
				EntryIDs: []int64{entry.ID},
			})
		}
	}

	return conflicts
}

func validateTeacherSubjects(schedule Schedule) []Conflict {
	allowed := make(map[[2]int64]bool)

	for _, assignment := range schedule.TeacherSubjectAssignments {
		allowed[[2]int64{assignment.TeacherID, assignment.SubjectID}] = true
	}

	conflicts := make([]Conflict, 0)

	for _, entry := range schedule.Entries {
		if !allowed[[2]int64{entry.TeacherID, entry.SubjectID}] {
			conflicts = append(conflicts, Conflict{"teacher_subject_mismatch", "Teacher is not assigned to this subject.", []int64{entry.ID}})
		}
	}

	return conflicts
}

func validateTeacherUnavailability(schedule Schedule) []Conflict {
	conflicts := make([]Conflict, 0)

	for _, entry := range schedule.Entries {
		start, end, ok := parseRange(entry.TimeSlotStartsAt, entry.TimeSlotEndsAt)
		if !ok {
			conflicts = append(conflicts, Conflict{"invalid_time_slot", "Schedule entry has an invalid time slot range.", []int64{entry.ID}})
			continue
		}

		for _, rule := range schedule.TeacherUnavailabilityRules {
			if rule.TeacherID != entry.TeacherID || rule.DayOfWeek != entry.DayOfWeek {
				continue
			}

			unavailableFrom, unavailableTo, ok := parseRange(rule.UnavailableFrom, rule.UnavailableTo)
			if !ok {
				conflicts = append(conflicts, Conflict{"invalid_teacher_unavailability", "Teacher unavailability has an invalid time range.", []int64{entry.ID}})
				continue
			}

			if timeRangesOverlap(start, end, unavailableFrom, unavailableTo) {
				conflicts = append(conflicts, Conflict{"teacher_unavailability_conflict", "Teacher is unavailable at this time.", []int64{entry.ID}})
			}
		}
	}

	return conflicts
}

func validateTeachingLoads(schedule Schedule) []Conflict {
	scheduledCounts := make(map[int64]int)

	for _, entry := range schedule.Entries {
		count := lessonCount(entry.WeekParity)

		for _, teachingLoadID := range entry.TeachingLoadIDs {
			scheduledCounts[teachingLoadID] += count
		}
	}

	conflicts := make([]Conflict, 0)

	for _, teachingLoad := range schedule.TeachingLoads {
		scheduledCount := scheduledCounts[teachingLoad.ID]

		if scheduledCount < teachingLoad.RequiredLessonCount {
			conflicts = append(conflicts, Conflict{
				Type:     "teaching_load_missing",
				Message:  fmt.Sprintf("Teaching load %d requires %d lessons, but only %d are scheduled.", teachingLoad.ID, teachingLoad.RequiredLessonCount, scheduledCount),
				EntryIDs: entryIDsForTeachingLoad(schedule.Entries, teachingLoad.ID),
			})
		}

		if scheduledCount > teachingLoad.RequiredLessonCount {
			conflicts = append(conflicts, Conflict{
				Type:     "teaching_load_overscheduled",
				Message:  fmt.Sprintf("Teaching load %d requires %d lessons, but %d are scheduled.", teachingLoad.ID, teachingLoad.RequiredLessonCount, scheduledCount),
				EntryIDs: entryIDsForTeachingLoad(schedule.Entries, teachingLoad.ID),
			})
		}
	}

	return conflicts
}

func entryIDsForTeachingLoad(entries []ScheduleEntry, teachingLoadID int64) []int64 {
	entryIDs := make([]int64, 0)

	for _, entry := range entries {
		if slices.Contains(entry.TeachingLoadIDs, teachingLoadID) {
			entryIDs = append(entryIDs, entry.ID)
		}
	}

	return entryIDs
}

func lessonCount(weekParity string) int {
	if weekParity == "both" {
		return 2
	}

	return 1
}

func weekParityOverlaps(left string, right string) bool {
	return left == "both" || right == "both" || left == right
}

func hasInt64Overlap(left []int64, right []int64) bool {
	rightValues := make(map[int64]bool, len(right))

	for _, value := range right {
		rightValues[value] = true
	}

	for _, value := range left {
		if rightValues[value] {
			return true
		}
	}

	return false
}

func parseRange(start string, end string) (time.Time, time.Time, bool) {
	startTime, startErr := time.Parse("15:04:05", start)
	endTime, endErr := time.Parse("15:04:05", end)

	if startErr != nil || endErr != nil {
		return time.Time{}, time.Time{}, false
	}

	return startTime, endTime, true
}

func timeRangesOverlap(leftStart time.Time, leftEnd time.Time, rightStart time.Time, rightEnd time.Time) bool {
	return leftStart.Before(rightEnd) && rightStart.Before(leftEnd)
}
