package validation

const (
	WeekParityOdd  = 1
	WeekParityEven = 2
	WeekParityBoth = 3
)

const (
	WeekParityNameOdd  = "odd"
	WeekParityNameEven = "even"
	WeekParityNameBoth = "both"
)

func LessonCountFromWeekParity(value int) int {
	if value == WeekParityBoth {
		return 2
	}

	return 1
}

func LessonCountFromWeekParityName(value string) int {
	if value == WeekParityNameBoth {
		return 2
	}

	return 1
}

func WeekParityOverlapsInt(left int, right int) bool {
	return left == WeekParityBoth || right == WeekParityBoth || left == right
}

// SubgroupsOverlap reports whether two subgroup values share students. A zero
// value means the whole group and overlaps any subgroup; distinct non-zero
// values (1 vs 2) are disjoint.
func SubgroupsOverlap(left int, right int) bool {
	return left == 0 || right == 0 || left == right
}

func effectiveStudentCount(studentCount int, subgroup int) int {
	if subgroup == 0 {
		return studentCount
	}

	return (studentCount + 1) / 2
}
