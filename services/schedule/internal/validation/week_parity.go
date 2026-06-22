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
