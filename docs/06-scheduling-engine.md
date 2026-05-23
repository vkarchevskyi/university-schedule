# Scheduling Engine

## Purpose

The scheduling engine creates or validates schedules using deterministic rules. It may use optimization techniques, but schedule validity is based on explicit constraints rather than LLM output.

## Inputs

- Semester dates and first week parity.
- Groups and student counts.
- Teachers and their subject associations.
- Teacher unavailability ranges.
- Subjects and teaching-load requirements.
- Rooms with type and capacity.
- Time slots.
- Existing schedule entries if generation is partial.
- Hard and soft constraint configuration.

## Outputs

- Candidate schedule entries.
- Validation result.
- Conflict list.
- Optional score for soft constraints.
- Generation status and diagnostics.

## Hard Constraints

A schedule is invalid if any hard constraint is violated.

- A group cannot have two classes at the same day, time slot, and applicable week parity.
- A teacher cannot teach two classes at the same day, time slot, and applicable week parity.
- A room cannot host two classes at the same day, time slot, and applicable week parity.
- Room capacity must fit the total number of students in all assigned groups.
- Teacher must be linked to the subject they are assigned to teach.
- Teacher cannot be assigned during unavailable ranges.
- Schedule entries must belong to the selected semester and valid schedule period.
- Schedule entries must match their linked teaching-load requirements by semester, subject, teacher, lesson type, and group.
- Required teaching-load counts must be satisfied before a schedule can be considered complete.
- Required fields must be present.

## Soft Constraints

Soft constraints influence quality but do not automatically make a schedule invalid.

- Minimize windows for groups.
- Minimize windows for teachers.
- Avoid overloaded days.
- Prefer suitable room type for subject type.
- Prefer compact distribution across the week.
- Avoid late slots when earlier valid slots exist.
- Prefer scheduling teaching-load cards in a way that reduces unsatisfied requirements early.

Soft constraints should produce a score and explanation so administrators can understand tradeoffs.

## Generation Workflow

1. API creates generation job for a semester.
2. API validates that active teaching-load requirements, rooms, time slots, and teacher-subject links exist.
3. API sends message to RabbitMQ.
4. Go worker loads input data.
5. Go worker generates a candidate schedule using CSP for feasible construction and Tabu search for optimization.
6. Go worker validates hard constraints.
7. Go worker writes generated entries directly to PostgreSQL as a draft or generated schedule.
8. API exposes status and result to admin.
9. Admin reviews and publishes.

The first implementation may use deterministic CSP placement with a bounded score calculation before deeper optimization is tuned against real datasets. A generated draft is acceptable only when hard validation passes and the quality score is at least 80/100.

## Validation Workflow

Validation should be available independently from generation.

Validation-only logic uses CSP-style constraint checking for hard constraints. Tabu search is used for optimization during generation, not for basic validation.

The Go schedule service owns the full validation rule implementation. Symfony may keep lightweight editor guardrails for fast API feedback, but publication validation must delegate to the Go service to avoid rule drift between manual publishing and automatic generation.

Use cases:

- Validate a draft before publishing.
- Validate manual entry creation.
- Validate manual entry update.
- Validate a generated candidate.
- Validate teaching-load completion.

## Conflict Shape

```json
{
  "type": "room_conflict",
  "severity": "hard",
  "message": "Room is already occupied for this time slot.",
  "entryIds": [11, 12],
  "dayOfWeek": 2,
  "timeSlotId": 3
}
```

## Implementation Notes

- Keep validation logic reusable between manual editing and publishing.
- Avoid duplicating core rules across frontend, backend, and Go.
- If both Symfony and Go need validation, define shared test fixtures and expected results.
- Generated schedules should be drafts until confirmed by an administrator.
- Treat teaching-load rows as the demand side of the problem and schedule entries as the placement side.
- Reject generation requests before queueing when the demand side is incomplete or contains teacher-subject mismatches.
- Week parity affects how many actual lesson occurrences a schedule entry contributes toward a teaching-load requirement.
- Use a common initial minimum quality threshold for generated drafts, then tune it with real data. A practical first threshold is that all hard constraints must pass and the soft-constraint score should be at least 80 out of 100 before presenting the draft as acceptable. Scores below that can still be saved for diagnostics but should be marked as low quality.

## Decisions

- Go writes generated entries directly to PostgreSQL.
- Generation uses CSP for feasible construction and Tabu search for optimization.
- Validation uses CSP-style hard-constraint checking.
- Initial generated-draft quality target: no hard conflicts and at least 80/100 soft score. Tune later.
