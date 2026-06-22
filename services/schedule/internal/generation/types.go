package generation

type JobMessage struct {
	JobID             string `json:"jobId"`
	SemesterID        int64  `json:"semesterId"`
	RequestedByUserID int64  `json:"requestedByUserId"`
	BaseScheduleID    *int64 `json:"baseScheduleId,omitempty"`
}

type Result struct {
	ScheduleID    int64
	QualityScore  int
	QualityStatus string
	Diagnostics   map[string]any
}

type JobResource struct {
	ID                  string         `json:"id"`
	SemesterID          int64          `json:"semesterId"`
	RequestedBy         int64          `json:"requestedBy"`
	Status              string         `json:"status"`
	GeneratedScheduleID *int64         `json:"generatedScheduleId"`
	QualityScore        *int           `json:"qualityScore"`
	QualityStatus       *string        `json:"qualityStatus"`
	ErrorMessage        *string        `json:"errorMessage"`
	Diagnostics         map[string]any `json:"diagnostics"`
	CreatedAt           string         `json:"createdAt"`
	StartedAt           *string        `json:"startedAt"`
	FinishedAt          *string        `json:"finishedAt"`
}

type Semester struct {
	ID       int64
	StartsAt string
	EndsAt   string
}

type TeachingLoad struct {
	ID                   int64
	GroupID              int64
	SubjectID            int64
	TeacherID            int64
	LessonType           int
	RequiredLessonCount  int
	RequiresComputerRoom bool
	StudentCount         int
}

type Room struct {
	ID       int64
	Type     string
	Capacity int
}

type TimeSlot struct {
	ID       int64
	Number   int
	StartsAt string
	EndsAt   string
}

type CandidateEntry struct {
	TeachingLoadID       int64
	GroupID              int64
	SubjectID            int64
	TeacherID            int64
	LessonType           int
	RoomID               int64
	RoomType             string
	RoomCapacity         int
	TimeSlotID           int64
	TimeSlotNumber       int
	TimeSlotStartsAt     string
	TimeSlotEndsAt       string
	DayOfWeek            int
	WeekParity           int
	StudentCount         int
	RequiresComputerRoom bool
}
