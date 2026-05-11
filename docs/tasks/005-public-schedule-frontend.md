# Task: Public Schedule Frontend

## Goal

Create the public web view for browsing published schedules.

## Context

Read before starting:

- `docs/00-product-brief.md`
- `docs/04-api-contracts.md`
- `docs/08-frontend-ux.md`

The public frontend should be immediately useful without a landing page.

## Scope

- Add schedule filter controls for group, teacher, room, and week.
- Render a weekly schedule grid on desktop.
- Render a mobile-friendly day-by-day view.
- Show empty, loading, and error states.
- Fetch data from the public schedule API.

## Out Of Scope

- Admin UI.
- Authentication.
- Drag-and-drop editing.
- Telegram features.

## Acceptance Criteria

- User can select entity type and entity.
- User can move between weeks.
- Schedule data renders clearly on desktop and mobile.
- Long names wrap without breaking layout.
- Frontend tests or e2e coverage verify basic rendering.

## Suggested Files Or Areas

- `frontend/src/App.vue`
- `frontend/src/router`
- `frontend/src/components`
- `frontend/src/services`
- `frontend/src/__tests__`
- `frontend/e2e`

## Verification

- `pnpm lint`
- `pnpm test:unit`
- `pnpm build`

