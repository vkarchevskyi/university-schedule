# Project Documentation

Start here when using AI-driven development for this project.

## Reading Order

1. `00-product-brief.md` - product purpose, users, MVP, and architecture summary.
2. `01-requirements.md` - functional and nonfunctional requirements.
3. `02-architecture.md` - service boundaries and main request flows.
4. `03-data-model.md` - entities, relationships, and integrity rules.
5. `04-api-contracts.md` - intended REST API contracts.
6. `05-ai-assistant.md` - rules for LLM-assisted Telegram queries.
7. `06-scheduling-engine.md` - validation and generation rules.
8. `07-telegram-bot.md` - Telegram command and notification behavior.
9. `08-frontend-ux.md` - public and admin frontend expectations.
10. `09-dev-setup.md` - local environment notes.
11. `10-deployment.md` - release and deployment checklist.

## Task Docs

Implementation tasks live in `tasks/`.

Recommended first sequence:

1. `tasks/001-complete-local-dev-environment.md`
2. `tasks/002-admin-auth.md`
3. `tasks/003-entity-crud.md`
4. `tasks/004-public-schedule-api.md`
5. `tasks/005-public-schedule-frontend.md`
6. `tasks/006-manual-schedule-editor.md`
7. `tasks/007-schedule-validation-and-publishing.md`
8. `tasks/008-generation-service.md`
9. `tasks/012-exam-scheduling.md`
10. `tasks/009-telegram-bot.md`
11. `tasks/010-ai-query-parser.md`
12. `tasks/011-deploy-demo-release.md`

The generation service and exam scheduling are required for the first shipped release, so do not treat them as optional second-phase work.

## AI Coding Prompt Template

```text
Read docs/00-product-brief.md and docs/tasks/<task-file>.md.
Inspect the existing code before editing.
Implement only the task scope.
Do not change unrelated files.
Update docs if the implementation changes a documented contract.
Run the relevant checks and summarize what changed.
```

## Clarification Policy

If a task hits an open question that affects architecture or user-visible behavior, ask before implementation. If the question is minor and reversible, make a conservative assumption and record it in the relevant doc.
