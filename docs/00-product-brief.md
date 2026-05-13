# University Schedule Product Brief

## Purpose

University Schedule is a web system for creating, validating, publishing, and viewing university class schedules. The product combines deterministic scheduling logic with AI-assisted natural-language interaction. The scheduling rules stay inside the application and generation service; AI is used to interpret user requests and improve access to schedule information.

The product is based on the qualification paper `Кваліфікаційна робота Карчевського Володимира (3).txt`, especially the sections about requirements, architecture, database design, schedule generation, the frontend, and the Telegram bot.

## Problem

Manual schedule creation with spreadsheets is slow and error-prone. It is difficult to prevent room conflicts, teacher conflicts, group overlaps, capacity violations, and inconvenient gaps. After a valid schedule exists, students and teachers still need quick access to current information and changes.

## Users

- Administrator: manages academic data, creates and edits schedules, validates conflicts, publishes schedules, and reviews changes.
- Student: views the current schedule for a group and receives updates through Telegram.
- Teacher: views a personal schedule and may receive updates through Telegram.
- Public visitor: views published schedules without authentication.

## Core Value

- Reduce schedule creation time from days to hours.
- Prevent invalid schedules before publication.
- Provide public web access to the actual schedule.
- Provide Telegram access and notifications for quick communication.
- Use AI for natural-language request parsing without making AI responsible for authoritative scheduling decisions.

## MVP Scope

- Admin authentication.
- CRUD for academic entities: groups, teachers, subjects, rooms, time slots, academic years, semesters.
- Public weekly schedule view by group, teacher, or room.
- Manual schedule creation and editing.
- Conflict validation for group, teacher, room, room capacity, teacher subject compatibility, teacher availability, and semester boundaries.
- Schedule publication flow.
- Change log for administrative schedule changes.
- Telegram bot commands for start, schedule lookup, subscribe, and unsubscribe.
- AI intent parsing for free-text Telegram schedule questions.
- Automatic class schedule generation through the Go service.
- Exam scheduling for the first shipped version.
- Table-first admin editor with draggable lesson cards.

## Later Scope

- Advanced soft-constraint optimization and schedule scoring.
- Advanced drag-and-drop editor refinements beyond the first table-first card workflow.
- Real-time web notifications.
- Historical schedule rollback.
- Automatic exam schedule generation can be improved after the first shipped version if the initial release only includes basic generation.

## Non-Goals

- Student or teacher web accounts in the MVP.
- AI directly writing official schedules.
- Replacing deterministic validation with LLM judgment.
- Supporting multiple institutions before the single-institution model is stable.

## Architecture Summary

- Frontend: Vue SPA.
- Backend: Symfony REST API.
- Database: PostgreSQL.
- Cache: Redis.
- Async messaging: RabbitMQ.
- Generation service: Go worker for resource-intensive schedule construction.
- Telegram bot: integrated into the Symfony API through Telegram webhooks.
- AI integration: Gemini API, used for intent parsing.

## Shippable Definition

The app is shippable when an administrator can create academic entities, create or import a schedule, validate it, publish it, and users can view the current published schedule through the web UI and Telegram. Invalid schedules must not be publishable.

## AI Development Rule

Future AI coding sessions should read this file first, then read the task-specific file under `docs/tasks`. The task file controls scope. If implementation choices conflict with this product brief, stop and update the docs or ask for clarification before coding.
