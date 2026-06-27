# Evidence -- screenshots

Visual evidence for the PulseDesk agent build. The working app is also verifiable **live**
(below), so judges can click through real flows directly, not just from screenshots.

## Live deployment (preferred verification)

- Frontend (React, Vercel): https://forge2-chaman-kumar.vercel.app/
- Backend API (Laravel, Render): https://pulsedesk-api-wvmi.onrender.com

Demo logins (seeded): `admin@acme.test` / `password` (Org Acme),
`admin@globex.test` / `password` (Org Globex) -- sign in as each to see tenant isolation.

## Slack channel evidence (the agent loop, in the open)

| File | Shows |
|---|---|
| `sprint-main.png` | human <-> Hermes: sprint goals and the plan/backlog Hermes posts back |
| `agent-coder.png` | Hermes -> OpenClaw scoped task handoffs |
| `agent-log.png`   | OpenClaw structured reports (What I Did / What's Left / What Needs Your Call) |
| `ci-cd.png`       | GitHub Actions CI results posted to #ci-cd via webhook |
| `agent-conv.png`  | the two bots (`@hermes_brain` + `@openclaw_worker`) conversing |
| `qa.png`          | the bonus QA reviewer's code-review findings |

The full machine-readable Slack export (channels.json, users.json, per-channel dated JSON) is in
`slack-export/`, and cross-checks against the git/PR history and the CI runs.

## App and CI

- The working application is live at the URLs above and runs locally from a fresh clone
  (see the root `README.md` run steps); `php artisan test` is green (52 passing).
- CI green runs are on the repo's GitHub **Actions** tab and were posted to `#ci-cd`
  (see `ci-cd.png` and `slack-export/ci-cd/`).
