# Task: AI Query Parser

## Goal

Allow Telegram users to ask schedule questions in natural language and convert those messages into structured schedule lookups.

## Context

Read before starting:

- `docs/05-ai-assistant.md`
- `docs/07-telegram-bot.md`
- `docs/04-api-contracts.md`

The AI parser improves user experience but must not become a source of truth.

## Scope

- Add an AI provider service boundary.
- Use Gemini API as the first provider.
- Add prompt for strict JSON intent output.
- Validate model responses against the supported schema.
- Resolve extracted entity names against database data.
- Connect `get_schedule` intent to the existing schedule lookup service.
- Add fallback messages for ambiguity, low confidence, invalid JSON, and provider failure.
- Include Ukrainian prompt examples and user-facing fallback messages.

## Out Of Scope

- AI schedule generation.
- Admin mutations.
- Long conversational memory.
- RAG unless explicitly required later.

## Acceptance Criteria

- Free-text messages such as "show KN-22 schedule tomorrow" are parsed into schedule lookups.
- Invalid or ambiguous requests produce clarification instead of broken behavior.
- LLM output is schema-validated before use.
- Schedule answer is based on database/API data.
- Provider errors are handled gracefully.

## Suggested Files Or Areas

- `rest-api/src/Service/AI`
- `rest-api/src/Service/Telegram`
- `rest-api/config/services.yaml`
- `rest-api/tests`
