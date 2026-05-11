# Task: Admin Authentication

## Goal

Allow administrators to log in and protect administrative API routes.

## Context

Read before starting:

- `docs/00-product-brief.md`
- `docs/01-requirements.md`
- `docs/04-api-contracts.md`

The app has public schedule viewing and admin-only mutations. Students and teachers do not have web accounts in the MVP.

## Scope

- Implement admin login.
- Hash and verify admin passwords.
- Add current-admin endpoint.
- Use JWT for API authentication.
- Protect admin routes.
- Return clear 401/403 responses.
- Add tests for successful login, failed login, and protected route access.

## Out Of Scope

- Student accounts.
- Teacher accounts.
- Password reset.
- Registration flow unless needed for local seed/admin creation.

## Acceptance Criteria

- Admin can authenticate with email and password.
- Invalid credentials are rejected.
- Anonymous users cannot access admin-only routes.
- Public schedule routes remain public.

## Suggested Files Or Areas

- `rest-api/src/Entity/Admin.php`
- `rest-api/src/Repository/AdminRepository.php`
- `rest-api/config/packages/security.yaml`
- `rest-api/src/Controller`
- `rest-api/tests`

## Decisions

- Use JWT for Symfony API authentication.
