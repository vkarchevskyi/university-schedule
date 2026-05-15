# AI Assistant

## Role

The AI assistant improves access to schedule information by converting natural-language messages into structured intents. It is not the source of truth and does not decide whether a schedule is valid.

## Allowed Responsibilities

- Parse user text into a known intent.
- Extract entities such as group name, teacher name, date, week, and time range.
- Ask a clarification question when the request is ambiguous.
- Produce short human-readable explanations based on verified API data.
- Help format Telegram responses.

## Forbidden Responsibilities

- Invent schedule entries.
- Modify official schedule data directly.
- Bypass conflict validation.
- Answer schedule questions from model memory.
- Expose private admin data.
- Treat unvalidated LLM JSON as trusted.

## Supported Intents

### `get_schedule`

User wants a schedule.

Fields:

- `targetType`: `group`, `teacher`, or `room`
- `targetName`: user-provided name
- `date`: optional ISO date
- `weekStart`: optional ISO date
- `range`: `today`, `tomorrow`, `week`, or `date`

### `subscribe`

User wants to subscribe to changes.

Fields:

- `targetType`: `group` or `teacher`
- `targetName`: user-provided name

### `unsubscribe`

User wants to unsubscribe.

Fields:

- `targetType`: optional
- `targetName`: optional

### `help`

User needs usage help.

### `unknown`

The assistant cannot confidently map the message to a supported intent.

## Expected LLM JSON

```json
{
  "intent": "get_schedule",
  "confidence": 0.92,
  "targetType": "group",
  "targetName": "KN-22",
  "date": null,
  "weekStart": "2026-09-07",
  "range": "week",
  "clarificationQuestion": null
}
```

## Validation Rules

- JSON must parse successfully.
- `intent` must be one of the supported values.
- `confidence` must be a number between `0` and `1`.
- Schedule lookup requires enough information to resolve a group, teacher, or room.
- Low-confidence responses should fall back to a clarification question.
- Entity names from the LLM must be resolved against the database.

## Prompting Guidelines

The system prompt should:

- Explain that the assistant only returns JSON.
- List supported intents and fields.
- Tell the model not to answer from memory.
- Tell the model to use `unknown` or a clarification question when unsure.
- Include Ukrainian examples because Ukrainian is required for the first release. English examples may be added for developer clarity.

## Error Handling

- If the LLM is unavailable, return a friendly temporary-unavailable message.
- If JSON is invalid, retry once with a stricter repair prompt.
- If the entity cannot be found, ask the user to choose from matching groups, teachers, or rooms.
- If the request is ambiguous, ask one concise clarification question.

## Provider

Gemini API is the selected first-release provider. The Symfony API uses Symfony AI packages for provider integration and keeps provider-specific code behind `rest-api/src/Service/AI`.

Runtime configuration:

- `GEMINI_API_KEY`
- `GEMINI_MODEL`, default `gemini-2.5-flash`

The Telegram handler treats confidence below `0.70` as ambiguous and returns a Ukrainian clarification message instead of executing the intent.
