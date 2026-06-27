# Sprint 03 — QA Hardening + CI Visibility

Goal: Close coverage gaps on tenant isolation, authorization, dashboard/SLA math, notifications, and wire CI results into Slack. Max 5 scoped issues.

Models: Hermes=deepseek/deepseek-v4-pro, OpenClaw=z-ai/glm-5.1

## Issues

- [ ] **PD-17** — Tenant isolation for related resources  
  Verify a user from Org A gets 404 on Org B's comments (`/api/tickets/{id}/comments`), activity logs (`/api/tickets/{id}/activity`), and notifications (`/api/notifications`). Also confirm that the ticket index only returns the caller's org tickets. Fix any real scoping bug found.

- [ ] **PD-18** — Customer policy hardening  
  Add explicit tests and enforcement that a customer cannot PATCH tickets, cannot assign, and only admins can DELETE. Confirm `TicketPolicy` covers `destroy` as admin-only. A customer attempting to delete their own ticket must receive 403 (not 404).

- [ ] **PD-19** — Dashboard metrics + SLA breach math sanity tests  
  Add tests for `DashboardController::metrics()` ensuring counts are tenant-scoped, avg first response returns correct minutes, SLA breach rate is accurate (0–100), and per-day grouping returns exactly the last 7 days. Cover edge cases: zero tickets, null `first_responded_at`, missing SLA policy.

- [ ] **PD-20** — Notification coverage on assign + reply, counterpart-only  
  Verify notifications are created when a ticket is assigned (notify the assignee) and when a public reply is posted (notify the requester). Ensure the actor does NOT receive a notification for their own action. Add missing notification logic if not present.

- [ ] **PD-21** — CI Slack webhook + frontend build verification  
  Confirm the frontend builds cleanly (`npm install && npm run build`). Add Slack notification steps to both backend and frontend jobs in `.github/workflows/ci.yml` using the `SLACK_WEBHOOK_URL` repo secret (Option 1 from the CI/CD guide). Open a PR. Do NOT merge.

## Outcome
- QA coverage hardened across all identified risks.
- CI posts green/red results to `#ci-cd` automatically.
- PRs opened by OpenClaw; merged by human after review.
