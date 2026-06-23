package validation

import "testing"

func TestLessonCountFromWeekParity(t *testing.T) {
	tests := []struct {
		name       string
		weekParity int
		want       int
	}{
		{name: "odd", weekParity: WeekParityOdd, want: 1},
		{name: "even", weekParity: WeekParityEven, want: 1},
		{name: "both", weekParity: WeekParityBoth, want: 2},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			if got := LessonCountFromWeekParity(tt.weekParity); got != tt.want {
				t.Fatalf("LessonCountFromWeekParity(%d) = %d, want %d", tt.weekParity, got, tt.want)
			}
		})
	}
}

func TestWeekParityOverlapsInt(t *testing.T) {
	if !WeekParityOverlapsInt(WeekParityOdd, WeekParityBoth) {
		t.Fatal("odd and both should overlap")
	}
	if WeekParityOverlapsInt(WeekParityOdd, WeekParityEven) {
		t.Fatal("odd and even should not overlap")
	}
}

func TestSubgroupsOverlap(t *testing.T) {
	tests := []struct {
		name  string
		left  int
		right int
		want  bool
	}{
		{name: "whole group overlaps subgroup", left: 0, right: 1, want: true},
		{name: "subgroup overlaps whole group", left: 2, right: 0, want: true},
		{name: "equal subgroups overlap", left: 1, right: 1, want: true},
		{name: "distinct subgroups disjoint", left: 1, right: 2, want: false},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			if got := SubgroupsOverlap(tt.left, tt.right); got != tt.want {
				t.Fatalf("SubgroupsOverlap(%d, %d) = %v, want %v", tt.left, tt.right, got, tt.want)
			}
		})
	}
}
