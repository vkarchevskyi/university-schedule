# Telegram Bot

## Purpose

The Telegram bot gives students and teachers quick access to schedule data and notifications about changes.

The first release must support Ukrainian-language interaction.

## Architecture

The bot is integrated into the Symfony API through a Telegram webhook endpoint. It is not a separate service.

Expected library from the paper:

- NutGram for Telegram Bot API integration with Symfony.

## Commands

### `/start`

Introduces available actions and basic usage.

### `/schedule`

Returns the schedule for a selected group or teacher. The command should support week navigation through inline buttons.

### `/subscribe`

Starts a multi-step flow:

1. Choose subscription type: group or teacher.
2. Choose the concrete group or teacher.
3. Save subscription if it does not already exist.

### `/unsubscribe`

Allows the user to remove one or more subscriptions.

## Free-Text Messages

Free-text messages are passed to the AI intent parser. If the intent is `get_schedule`, the bot resolves the entity against the database and returns schedule data through the normal schedule service.

## State

Redis should be used for temporary multi-step command state. Avoid server sessions for Telegram flows.

## Schedule Formatting

- Format schedule data into compact text.
- Use Telegram MarkdownV2 or another selected parse mode consistently.
- Escape user and database content before sending.
- Split long messages into multiple parts when necessary.

## Notifications

Users should receive notifications when a subscribed group or teacher has relevant schedule changes.

Subscriptions are limited to groups and teachers in the first release. Room subscriptions are out of scope.

Trigger examples:

- Schedule publication.
- Lesson cancellation.
- Lesson room change.
- Lesson time change.
- Teacher change.

Notification sending should be queued and nearly immediate. It must be asynchronous or rate-limited to respect Telegram API limits.

## Security

- Verify webhook secret token or equivalent Telegram-provided mechanism.
- The bot must expose only public schedule information.
- The bot must never allow admin mutations.

## Decisions

- Subscriptions support groups and teachers only.
- Notifications should be queued and sent almost immediately.
- Ukrainian is required in the first release.
