---
name: laravel-tests
description: Run and triage the PulseDesk backend test suite, then report results to Slack.
---

# Laravel Tests

Use this skill when an issue touches the backend API, models, or policies.

## Procedure
1. From `backend/`, run the suite:
   ```
   php artisan test
   ```
2. If a test fails, read the assertion, open the relevant file, and fix the root cause.
   Do not weaken a test to make it pass. Tenant-isolation tests are non-negotiable.
3. Re-run until green.
4. Post a short result line to `#ci-cd`:
   `tests: N passed / M failed — <one-line summary>`

## Conventions
- Feature tests live in `backend/tests/Feature`.
- Use `RefreshDatabase` and the model factories.
- Authenticate with `$this->actingAs($user, 'sanctum')`.
- Always cover the org-scoping path for any new tenant-owned model.
