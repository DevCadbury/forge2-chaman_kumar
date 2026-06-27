# Sprint 04 — More PRs + CI Visibility

Goal: Open multiple scoped PRs that exercise the CI pipeline and expand test coverage. Ensure CI runs on every PR and posts to Slack.

## Issues

- [ ] **PD-22** — CI pipeline setup  
  Create `.github/workflows/ci.yml` with backend (PHP 8.2 + MySQL 8) and frontend (Node 20 + Vite build) jobs. Add Slack notifications via `SLACK_WEBHOOK_URL` secret. Open PR. Verify CI runs and Slack posts green/red.

- [ ] **PD-23** — Add phpunit tests for auth endpoints  
  Cover register, login, logout, and `/api/me`. Assert validation errors, token returned, and logout revokes token.

- [ ] **PD-24** — Ticket filter tests  
  Test `?status=`, `?priority=`, `?assignee=`, `?q=` query params. Assert customers only see their own tickets. Assert staff see all org tickets.

- [ ] **PD-25** — Comment visibility tests  
  Confirm internal notes (`is_internal=true`) are hidden from customers. Confirm staff see all comments. Assert 403 when customer tries to post internal.

- [ ] **PD-26** — Activity log audit trail tests  
  Assert activity logs are created on ticket create, update, assign, and comment. Assert logs are tenant-scoped.

- [ ] **PD-27** — Notification read/unread tests  
  Test listing notifications, marking one read, and mark-all-read. Assert only the current user's notifications are returned.

- [ ] **PD-28** — SLA policy + SlaService tests  
  Test SLA due-time calculations and breach detection. Test edge cases: missing policy, zero tickets, null `first_responded_at`.

- [ ] **PD-29** — Frontend build + smoke test  
  Ensure `npm run build` passes cleanly. Add a simple smoke test that the built `dist/index.html` exists and contains expected markup.

- [ ] **PD-30** — README update with CI badge  
  Add a CI status badge to the README. Document how to run tests locally.

## Outcome
- CI runs on every push and PR.
- Slack `#ci-cd` receives pass/fail notifications.
- Multiple open PRs demonstrate active development and test coverage.
- All PRs pass CI before merge.
