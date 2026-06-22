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
	SemesterStartsAt           string                  `json:"semesterStartsAt"`
	SemesterEndsAt             string                  `json:"semesterEndsAt"`
	ValidFrom                  string                  `json:"validFrom"`
	ValidTo                    string                  `json:"validTo"`
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
	RoomType         string  `json:"roomType"`
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
	ID                   int64  `json:"id"`
	GroupID              int64  `json:"groupId"`
	SubjectID            int64  `json:"subjectId"`
	TeacherID            int64  `json:"teacherId"`
	LessonType           string `json:"lessonType"`
	RequiredLessonCount  int    `json:"requiredLessonCount"`
	RequiresComputerRoom bool   `json:"requiresComputerRoom"`
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

const (
	firstScheduleDay = 1
	lastScheduleDay  = 5
)

type Validator struct{}

func NewValidator() Validator {
	return Validator{}
}

func (Validator) Validate(schedule Schedule) ValidationResult {
	conflicts := make([]Conflict, 0)
	conflicts = append(conflicts, validateScheduleDays(schedule.Entries)...)
	conflicts = append(conflicts, validateEntryConflicts(schedule.Entries)...)
	conflicts = append(conflicts, validateCapacity(schedule.Entries)...)
	conflicts = append(conflicts, validateRoomRequirements(schedule)...)
	conflicts = append(conflicts, validateTeacherSubjects(schedule)...)
	conflicts = append(conflicts, validateTeacherUnavailability(schedule)...)
	conflicts = append(conflicts, validateSchedulePeriod(schedule)...)
	conflicts = append(conflicts, validateTeachingLoads(schedule)...)

	return ValidationResult{
		Valid:     len(conflicts) == 0,
		Conflicts: conflicts,
	}
}

func validateScheduleDays(entries []ScheduleEntry) []Conflict {
	conflicts := make([]Conflict, 0)

	for _, entry := range entries {
		if entry.DayOfWeek < firstScheduleDay || entry.DayOfWeek > lastScheduleDay {
			conflicts = append(conflicts, Conflict{
				Type:     "invalid_day_of_week",
				Message:  "Schedule entries can only be placed Monday through Friday.",
				EntryIDs: []int64{entry.ID},
			})
		}
	}

	return conflicts
}

func validateEntryConflicts(entries []ScheduleEntry) []Conflict {
	conflicts := make([]Conflict, 0)

	for leftIndex, left := range entries {
		for _, right := range entries[leftIndex+1:] {
			if left.DayOfWeek != right.DayOfWeek || !weekParityOverlaps(left.WeekParity, right.WeekParity) || !entryTimeRangesOverlap(left, right) {
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

func entryTimeRangesOverlap(left ScheduleEntry, right ScheduleEntry) bool {
	leftStart, leftEnd, leftOK := ParseRange(left.TimeSlotStartsAt, left.TimeSlotEndsAt)
	rightStart, rightEnd, rightOK := ParseRange(right.TimeSlotStartsAt, right.TimeSlotEndsAt)
	if !leftOK || !rightOK {
		return left.TimeSlotID == right.TimeSlotID
	}

	return TimeRangesOverlap(leftStart, leftEnd, rightStart, rightEnd)
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

func validateRoomRequirements(schedule Schedule) []Conflict {
	teachingLoadsByID := make(map[int64]TeachingLoad, len(schedule.TeachingLoads))
	conflicts := make([]Conflict, 0)

	for _, teachingLoad := range schedule.TeachingLoads {
		teachingLoadsByID[teachingLoad.ID] = teachingLoad
	}

	for _, entry := range schedule.Entries {
		for _, teachingLoadID := range entry.TeachingLoadIDs {
			teachingLoad, exists := teachingLoadsByID[teachingLoadID]
			if !exists || !teachingLoad.RequiresComputerRoom || entry.RoomType == "computer" {
				continue
			}

			conflicts = append(conflicts, Conflict{
				Type:     "room_type_conflict",
				Message:  fmt.Sprintf("Teaching load %d requires a computer room.", teachingLoad.ID),
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
		start, end, ok := ParseRange(entry.TimeSlotStartsAt, entry.TimeSlotEndsAt)
		if !ok {
			conflicts = append(conflicts, Conflict{"invalid_time_slot", "Schedule entry has an invalid time slot range.", []int64{entry.ID}})
			continue
		}

		for _, rule := range schedule.TeacherUnavailabilityRules {
			if rule.TeacherID != entry.TeacherID || rule.DayOfWeek != entry.DayOfWeek {
				continue
			}

			unavailableFrom, unavailableTo, ok := ParseRange(rule.UnavailableFrom, rule.UnavailableTo)
			if !ok {
				conflicts = append(conflicts, Conflict{"invalid_teacher_unavailability", "Teacher unavailability has an invalid time range.", []int64{entry.ID}})
				continue
			}

			if TimeRangesOverlap(start, end, unavailableFrom, unavailableTo) {
				conflicts = append(conflicts, Conflict{"teacher_unavailability_conflict", "Teacher is unavailable at this time.", []int64{entry.ID}})
			}
		}
	}

	return conflicts
}

func validateSchedulePeriod(schedule Schedule) []Conflict {
	if schedule.SemesterStartsAt == "" && schedule.SemesterEndsAt == "" && schedule.ValidFrom == "" && schedule.ValidTo == "" {
		return nil
	}

	semesterStart, startErr := time.Parse("2006-01-02", schedule.SemesterStartsAt)
	semesterEnd, endErr := time.Parse("2006-01-02", schedule.SemesterEndsAt)
	validFrom, validFromErr := time.Parse("2006-01-02", schedule.ValidFrom)
	validTo, validToErr := time.Parse("2006-01-02", schedule.ValidTo)

	if startErr != nil || endErr != nil || validFromErr != nil || validToErr != nil {
		return []Conflict{{Type: "invalid_schedule_period", Message: "Schedule has an invalid date range."}}
	}

	if validTo.Before(validFrom) || validFrom.Before(semesterStart) || validTo.After(semesterEnd) {
		return []Conflict{{Type: "schedule_period_outside_semester", Message: "Schedule validity period must be within the semester."}}
	}

	return nil
}

func validateTeachingLoads(schedule Schedule) []Conflict {
	scheduledCounts := make(map[int64]int)
	teachingLoadsByID := make(map[int64]TeachingLoad, len(schedule.TeachingLoads))
	conflicts := make([]Conflict, 0)

	for _, teachingLoad := range schedule.TeachingLoads {
		teachingLoadsByID[teachingLoad.ID] = teachingLoad
	}

	for _, entry := range schedule.Entries {
		count := lessonCount(entry.WeekParity)

		for _, teachingLoadID := range entry.TeachingLoadIDs {
			teachingLoad, exists := teachingLoadsByID[teachingLoadID]
			if !exists {
				conflicts = append(conflicts, Conflict{
					Type:     "teaching_load_mismatch",
					Message:  fmt.Sprintf("Schedule entry references unknown teaching load %d.", teachingLoadID),
					EntryIDs: []int64{entry.ID},
				})
				continue
			}

			if !entryMatchesTeachingLoad(entry, teachingLoad) {
				conflicts = append(conflicts, Conflict{
					Type:     "teaching_load_mismatch",
					Message:  fmt.Sprintf("Schedule entry does not match teaching load %d.", teachingLoad.ID),
					EntryIDs: []int64{entry.ID},
				})
				continue
			}

			scheduledCounts[teachingLoadID] += count
		}
	}

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

func entryMatchesTeachingLoad(entry ScheduleEntry, teachingLoad TeachingLoad) bool {
	return entry.SubjectID == teachingLoad.SubjectID &&
		entry.TeacherID == teachingLoad.TeacherID &&
		entry.LessonType == teachingLoad.LessonType &&
		slices.Contains(entry.GroupIDs, teachingLoad.GroupID)
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
	return LessonCountFromWeekParityName(weekParity)
}

func weekParityOverlaps(left string, right string) bool {
	return left == WeekParityNameBoth || right == WeekParityNameBoth || left == right
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

func ParseRange(start string, end string) (time.Time, time.Time, bool) {
	startTime, startErr := time.Parse("15:04:05", start)
	endTime, endErr := time.Parse("15:04:05", end)

	if startErr != nil || endErr != nil {
		return time.Time{}, time.Time{}, false
	}

	return startTime, endTime, true
}

func TimeRangesOverlap(leftStart time.Time, leftEnd time.Time, rightStart time.Time, rightEnd time.Time) bool {
	return leftStart.Before(rightEnd) && rightStart.Before(leftEnd)
}
