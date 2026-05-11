# Frontend UX

## Product Shape

The frontend is a Vue single-page application with two major modes:

- Public schedule viewer.
- Authenticated admin workspace.

The interface should feel like an operational university tool: clear, dense enough for repeated use, and optimized for scanning schedules.

Use shadcn-vue for most interface components.

## Public Schedule Viewer

Core controls:

- Entity type selector: group, teacher, room.
- Entity search/select.
- Week picker.
- Previous and next week buttons.

Desktop layout:

- Weekly grid by day and time slot.
- Class cards show subject, teacher, room, groups, and cancellation/override state.

Mobile layout:

- Use a stacked day-by-day view instead of a wide table.
- Preserve the same filtering logic.

## Admin Workspace

Core areas:

- Dashboard or schedule overview.
- Groups.
- Teachers.
- Subjects.
- Rooms.
- Time slots.
- Academic years and semesters.
- Schedules.
- Generation jobs.
- Action log.

Schedule editor expectations:

- Create, update, and delete schedule entries.
- Show draggable lesson cards derived from teaching-load requirements.
- Show remaining lesson count for each card, such as 8 required, 4 scheduled, 4 remaining.
- Allow a lesson card to be placed into a day/time-slot cell.
- Allow card week parity to be changed between both weeks, odd weeks, and even weeks.
- Use localized labels for lesson types, such as lecture, laboratory work, seminar, and practical class.
- Treat resizing as an editing gesture for the card's recurrence/week-parity behavior rather than as a separate persisted UI-only object.
- Show conflict feedback clearly.
- Allow validation before publication.
- Prevent publishing invalid schedules.
- Use a table-first editing model.
- Drag-and-drop placement is part of the table-first editor, but the underlying API must still support non-drag form editing for reliability and testing.

## Error Handling

- Form validation errors appear near the related fields.
- Schedule conflicts should highlight affected entries where possible.
- Async generation errors should be visible in the generation/job view.

## State Management

Use Pinia or a similarly consistent store pattern for:

- Current admin user.
- JWT auth token state.
- Global lookup data if needed.
- Notifications/toasts.

## Internationalization

- Use i18n dictionaries for interface copy.
- Provide English and Ukrainian dictionaries.
- Status labels and conflict language should come from i18n dictionaries, including draft, generated, published, archived, conflict, warning, and valid states.

## Visual Guidelines

- Use compact tables, grids, and forms for admin workflows.
- Prefer predictable controls over decorative layouts.
- Keep schedule cells stable in size to avoid layout jumps.
- Ensure long subject, teacher, and room names wrap cleanly.
- Do not put the primary app behind a marketing landing page.

## Decisions

- Admin schedule editor is table-first.
- shadcn-vue should be used for most of the interface.
- Status and conflict language should use English and Ukrainian i18n dictionaries.
