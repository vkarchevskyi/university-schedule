package generation

type JobMessage struct {
	JobID              string `json:"jobId"`
	SemesterID         int64  `json:"semesterId"`
	RequestedByUserID int64  `json:"requestedByUserId"`
}

type Result struct {
	ScheduleID    int64
	QualityScore  int
	QualityStatus string
	Diagnostics   map[string]any
}

type Semester struct {
	ID       int64
	StartsAt string
	EndsAt   string
}

type TeachingLoad struct {
	ID                  int64
	GroupID             int64
	SubjectID           int64
	TeacherID           int64
	LessonType          int
	RequiredLessonCount int
	StudentCount        int
}

type Room struct {
	ID       int64
	Capacity int
}

type TimeSlot struct {
	ID       int64
	StartsAt string
	EndsAt   string
}

type CandidateEntry struct {
	TeachingLoadID   int64
	GroupID          int64
	SubjectID        int64
	TeacherID        int64
	LessonType       int
	RoomID           int64
	RoomCapacity     int
	TimeSlotID       int64
	TimeSlotStartsAt string
	TimeSlotEndsAt   string
	DayOfWeek        int
	WeekParity       int
	StudentCount     int
}
