# Sprint 02 — Should Depth + React UI

Goal: Add SLA, queues, audit trail, metrics, and notifications, then build the React 19 client.
Models: Hermes=deepseek/deepseek-v4-pro, OpenClaw=z-ai/glm-5.1

## Issues
- [x] #10 SLA policies + `SlaService` (response/resolution due + breach state) surfaced on ticket
- [x] #11 Queues & assignment: claim/reassign/unassign + "unassigned" / "my tickets" filters
- [x] #12 Activity log / audit trail per ticket (created, status, priority, assign, replies)
- [x] #13 Dashboard metrics: by status/priority, avg first response, SLA breach rate, per-day
- [x] #14 In-app notifications on assignment + reply (`app_notifications`) + bell UI
- [x] #15 React 19 SPA: auth, ticket board (filters/search/create), ticket detail, dashboard
- [x] #16 CI: backend tests on MySQL 8 + frontend build on each PR

## Outcome
- Shipped: full Should-tier depth + polished React client; CI green.
- Slipped: Stretch items (canned responses, ticket merge, CSAT, real-time) — out of scope.
- PRs: merged to main after review (human).
