# Agent Instructions

These instructions apply to the `frontend/` Vue application. Use them for tasks that modify Vue, TypeScript, styles, frontend tests, or browser-facing behavior.

## Source Of Truth

- Product and architecture docs live in `../docs/`.
- API contracts live in `../docs/04-api-contracts.md`.
- Keep frontend behavior aligned with the Symfony REST API resources and public/admin route contracts.
- Do not commit local secrets, generated build output, or the qualification paper text file.

## General Workflow

- Read the relevant task doc in `../docs/tasks/` before implementing a feature.
- Keep changes scoped to the requested user workflow.
- Prefer existing app structure, naming, composables, stores, and API helpers over new patterns.
- Update docs when user-visible behavior, setup, environment variables, or API integration behavior changes.
- Run the relevant frontend verification commands before finishing.

## Architecture

Treat the frontend as a UI boundary over explicit API contracts.

Use this flow for data-driven UI:

```text
Page -> store/composable -> API client -> typed resource -> component props -> user interaction
```

Layer responsibilities:

- Pages: route-level composition, loading/error states, and user workflow orchestration.
- Stores: shared client state, caching decisions, and cross-page state transitions.
- API clients: HTTP calls, request/response typing, auth headers, and transport errors.
- Components: presentation, local interaction state, emitted events, and accessibility.
- Types: API resource/request types and frontend-only view models.
- Utils: small pure helpers with focused tests when behavior is non-trivial.
- Router: route definitions and navigation guards only.

Pages should stay thin. Avoid putting API parsing, large transformations, or reusable business rules directly in page components.

## Atomic Component Structure

Use the existing atomic structure under `src/components/`:

- `atoms`: smallest reusable primitives such as buttons, inputs, badges, icons, labels, and status chips.
- `molecules`: small compositions of atoms such as field rows, filters, search controls, table cells, and schedule cards.
- `organisms`: larger feature sections such as schedule grids, editors, forms, toolbars, and data tables.
- `pages`: route-level page components that assemble organisms and connect to stores/composables.

Rules:

- Keep atoms domain-light and highly reusable.
- Put domain-specific UI in molecules or organisms, not atoms.
- Do not put page-only business logic into atoms or molecules.
- Prefer props and emitted events over direct store access in atoms and molecules.
- Organisms may use stores when they own a complete feature section, but page-level orchestration should usually stay in pages.
- Avoid circular component dependencies between atomic layers.
- Create a new component only when it improves readability, reuse, testability, or separates a meaningful UI responsibility.

## Vue And TypeScript Practices

- Use Vue 3 Composition API with `<script setup lang="ts">`.
- Keep props and emits explicitly typed.
- Prefer computed state over duplicated mutable state.
- Keep watchers rare and justified; prefer computed values or explicit event handlers.
- Do not mutate props directly.
- Keep API response types separate from local UI state when the UI needs derived fields.
- Use Pinia for shared state. Keep local state inside components when it is not shared.
- Keep route names, params, and query handling typed where practical.
- Handle loading, empty, error, and success states for data-fetching views.
- Use i18n dictionaries for user-facing text. Do not hardcode new visible strings in components when the view already uses i18n.
- Keep dates and times explicit. Do not rely on browser locale defaults for schedule-critical formatting.

## UI And Accessibility

- Follow existing visual conventions before introducing new styling patterns.
- Prefer shadcn-vue components when available for common controls.
- Build table-first admin workflows for schedule editing and data management.
- Use semantic HTML for buttons, forms, tables, headings, and navigation.
- Ensure interactive elements are keyboard accessible.
- Provide labels or accessible names for form controls and icon-only buttons.
- Do not use color alone to communicate conflicts, drafts, generated schedules, or published schedules.
- Keep layouts responsive and verify important views on desktop and mobile widths.
- Avoid decorative UI that makes operational screens harder to scan.

## API Integration

- Keep HTTP calls in `src/api/` or established API helpers.
- Type request payloads and response resources according to `../docs/04-api-contracts.md`.
- Do not pass raw API errors directly to users. Map them to consistent frontend error states/messages.
- Keep authentication token handling centralized.
- Avoid duplicating backend validation rules unless needed for immediate client feedback. Backend validation remains authoritative.
- Do not invent API fields on the frontend. If a workflow needs new data, update the API contract and backend first or document the gap.

## Tests

Use focused tests at the level where behavior matters:

- Unit tests with Vitest for pure utilities, composables, stores, and non-trivial component behavior.
- Component tests with Vue Test Utils for props, emitted events, conditional rendering, and user interactions.
- E2E tests with Playwright for complete user workflows, routing, auth flows, schedule viewing/editing, and regression-prone integrations.

Testing rules:

- Add or update tests for every behavior change with meaningful user impact.
- Prefer testing public behavior over implementation details.
- Mock API boundaries for unit/component tests.
- Use realistic API resource shapes in fixtures.
- Keep fixtures small and local unless several tests genuinely share them.
- E2E tests should verify complete workflows, not every visual branch.
- Cover loading, empty, error, and success states when implementing data-fetching UI.
- Do not rely on test execution order.

## Frontend Verification

From `frontend/`:

```bash
npm run type-check
npm run lint
npm run format
npm run test:unit
npm run test:e2e
npm run build
```

Run the full set before finishing broad changes. For narrow edits, run the smallest relevant subset and state what was not run.

When changing browser-facing UI, also run the app locally and verify the affected view in a browser:

```bash
npm run dev
```

Use Playwright screenshots or browser checks for layout-sensitive work, especially schedule grids, drag-and-drop, tables, and responsive views.

## Change Discipline

- Keep diffs surgical and directly tied to the requested task.
- Do not refactor unrelated components while implementing a feature.
- Remove imports, props, state, fixtures, and tests made unused by your own changes.
- Match existing formatting and naming even when a different style would be acceptable.
- Prefer simple, explicit code over premature abstractions.
