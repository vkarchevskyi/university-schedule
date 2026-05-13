package examgeneration

type JobMessage struct {
	JobID              string `json:"jobId"`
	SemesterID         int64  `json:"semesterId"`
	RequestedByAdminID int64  `json:"requestedByAdminId"`
}

type Result struct {
	ExamScheduleID int64
	QualityScore   int
	QualityStatus  string
	Diagnostics    map[string]any
}

type Input struct {
	Semester                  Semester
	Demands                   []Demand
	Rooms                     []Room
	TimeSlots                 []TimeSlot
	TeacherSubjectAssignments map[teacherSubjectKey]bool
	ConsultationDaysBefore    int
	MinimumDaysBetweenExams   int
}

type Semester struct {
	ID       int64
	StartsAt string
	EndsAt   string
}

type Demand struct {
	SubjectID    int64
	TeacherID    int64
	GroupIDs     []int64
	StudentCount int
}

type Room struct {
	ID       int64
	Capacity int
}

type TimeSlot struct {
	ID       int64
	StartsAt string
}

type CandidateEntry struct {
	Type         int
	SubjectID    int64
	TeacherID    int64
	RoomID       int64
	RoomCapacity int
	EntryDate    string
	StartsAt     string
	GroupIDs     []int64
	StudentCount int
}

type teacherSubjectKey struct {
	TeacherID int64
	SubjectID int64
}
