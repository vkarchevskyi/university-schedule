# Task: User Authentication And Admin Authorization

## Goal

Allow users to log in and protect administrative API routes by role.

## Context

Read before starting:

- `docs/00-product-brief.md`
- `docs/01-requirements.md`
- `docs/04-api-contracts.md`

The app has public schedule viewing and admin-only mutations. Authenticated accounts are users with either `user` or `admin` roles. Students and teachers do not have web accounts in the MVP.

## Scope

- Implement user login.
- Hash and verify user passwords.
- Add current-user endpoint.
- Use JWT for API authentication.
- Protect admin routes.
- Restrict admin routes to users with the `admin` role.
- Return clear 401/403 responses.
- Add tests for successful login, failed login, and protected route access.

## Out Of Scope

- Student accounts.
- Teacher accounts.
- Password reset.
- Registration flow unless needed for local seed/admin creation.

## Acceptance Criteria

- User with the `admin` role can authenticate with email and password.
- Invalid credentials are rejected.
- Anonymous users cannot access admin-only routes.
- Users without the `admin` role cannot access admin-only routes.
- Public schedule routes remain public.

## Suggested Files Or Areas

- `rest-api/src/Entity/User.php`
- `rest-api/src/Repository/UserRepository.php`
- `rest-api/config/packages/security.yaml`
- `rest-api/src/Controller`
- `rest-api/tests`

## Decisions

- Use JWT for Symfony API authentication.
