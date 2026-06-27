# Evidence -- screenshots

Visual evidence for the PulseDesk agent build. The app is also verifiable **live** (URLs below),
so judges can click through real flows directly.

## Live deployment
- Frontend (React, Vercel): https://forge2-chaman-kumar.vercel.app/
- Backend API (Laravel, Render): https://pulsedesk-api-wvmi.onrender.com

Seeded logins: `admin@acme.test` / `password` (Org Acme), `admin@globex.test` / `password`
(Org Globex) -- sign in as each to see tenant isolation.

## Proof the EastRouter model is actually used (OpenClaw gotcha #7211)
| File | Shows |
|---|---|
| `evidence-API hits.png` | EastRouter API calls hitting the configured models while the agents answer |
| `evidence-credit usage.png` | EastRouter credit/usage drawn down by those calls (not a stub) |

## Agents running
| File | Shows |
|---|---|
| `evidence-hermes is running.png` | Hermes (`@hermes_brain`) gateway live in the terminal |
| `evidence-openclaw running.png` | OpenClaw (`@openclaw_worker`) gateway live in the terminal |

## App + multi-tenancy + CI
| File | Shows |
|---|---|
| `evidence-multi-tenancy.png` | Org A vs Org B -- same view, different data (tenant isolation) |
| `evidence-ci-cd.png` | green GitHub Actions CI run |

## Slack channel evidence (the agent loop, in the open)
| File | Shows |
|---|---|
| `sprint-main.png` | human <-> Hermes: sprint goals and the plan/backlog |
| `agent-coder.png` | Hermes -> OpenClaw scoped task handoffs |
| `agent-log.png`   | OpenClaw structured reports (What I Did / What's Left / What Needs Your Call) |
| `ci-cd.png`       | CI results posted to #ci-cd via webhook |
| `agent-conv.png`  | the two bots conversing |
| `qa.png`          | the bonus QA reviewer's findings |

Full machine-readable Slack export is in `slack-export/`; it cross-checks against the git/PR
history and the CI runs.
