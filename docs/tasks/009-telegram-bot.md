# Task: Telegram Bot Commands

## Goal

Implement basic Telegram bot commands for schedule lookup and subscriptions.

## Context

Read before starting:

- `docs/07-telegram-bot.md`
- `docs/04-api-contracts.md`
- `docs/05-ai-assistant.md`

Telegram is a fast access channel for students and teachers.

## Scope

- Add Telegram webhook endpoint.
- Verify webhook secret.
- Implement `/start`.
- Implement `/schedule`.
- Implement `/subscribe`.
- Implement `/unsubscribe`.
- Store subscriptions.
- Format schedule responses.
- Support Ukrainian-language bot copy.
- Send subscription notifications through a queue as near-immediate messages.

## Out Of Scope

- AI free-text parsing, unless implementing task 010 together.
- Admin actions through Telegram.
- Room subscriptions.

## Acceptance Criteria

- Telegram webhook receives and handles updates.
- User can request a schedule through command flow.
- User can subscribe and unsubscribe.
- Duplicate subscriptions are prevented.
- Bot does not expose admin operations.
- Subscription notifications support groups and teachers only.

## Suggested Files Or Areas

- `rest-api/src/Controller`
- `rest-api/src/Service/Telegram`
- `rest-api/src/Entity/TelegramSubscription.php`
- `rest-api/src/Repository/TelegramSubscriptionRepository.php`
