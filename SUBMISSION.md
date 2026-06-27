# Submission checklist — Forge 2 · Edition 1 (PulseDesk)

- [x] Repo is public, named `forge2-chaman_kumar`
- [x] README has exact run steps; `php artisan migrate --seed` works from a fresh clone
- [x] Backend = Laravel 11 + MySQL ; Frontend = React 19 + Vite + Tailwind
- [x] Multi-tenancy: Org A cannot see Org B data (tenant derived from auth session) — `app/Models/Scopes/OrganizationScope.php`, proven by `backend/tests/Feature/TenantIsolationTest.php`
- [x] Hermes config committed → `agents/hermes/hermes-config.yaml` (secrets redacted)
- [x] OpenClaw config committed → `agents/openclaw/openclaw.json` (secrets redacted)
- [ ] `agent-log.md` shows the real human → Hermes → OpenClaw loop  *(fill from your Slack run)*
- [x] `sprints/` has ≥ 2 sprint docs
- [ ] Slack proof in `slack-export/` (export) or `slack-export/screenshots/` (per channel)  *(add after the run)*
- [ ] App / agents-running / CI screenshots in `evidence/screenshots/`  *(add after the run)*
- [x] `.github/workflows/ci.yml` present — install → migrate → test (backend) + build (frontend)
- [ ] CI green run on the Actions tab  *(appears after first push/PR)*
- [ ] PRs merged by me (human)  *(do this via the agent loop)*
- [x] All model calls go through EastRouter (see agent configs)

## Summary
- **Models used:** Hermes `moonshotai/kimi-k2.7-code`, OpenClaw `z-ai/glm-5.1` (EastRouter)
- **Sprints run:** 2 (see `sprints/`)
- **Bonus agents:** OpenClaw reviewer/QA — `agents/openclaw/openclaw-reviewer.json`

## Feature coverage
- **Must:** multi-tenancy, auth + roles, ticket CRUD, threaded conversation (public + internal),
  list/filter/search, REST API + React UI, seeded demo data. ✔
- **Should:** SLA policies + timers, queues & assignment, activity audit trail, dashboard metrics,
  in-app notifications. ✔
