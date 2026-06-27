# Agent Log -- the human -> Hermes -> OpenClaw -> CI -> human loop

PulseDesk was built and hardened by orchestrating two **distinct Slack bots** under human
direction, in the open across six channels. This log is the real audit trail; it lines up with
the git history, the merged PRs, and the GitHub Actions runs posted to `#ci-cd`.

## The two agents (distinct Slack identities)

| Agent | Slack bot | Role | Model (EastRouter) |
|---|---|---|---|
| **Hermes Brain** | `@hermes_brain` (U0BEA0PMU48) | Product Owner / orchestrator -- plans sprints, assigns scoped issues, reviews reports. Never writes code. | `moonshotai/kimi-k2.7-code` |
| **OpenClaw** | `@openclaw_worker` (U0BDL9ZEGJE) | Coder -- implements one scoped issue at a time, runs `php artisan test`, opens PRs, reports. | `z-ai/glm-5.1` |
| **OpenClaw Reviewer (QA)** | bonus reviewer agent | Reviews PRs / code, posts findings | `z-ai/glm-5.1` |

Channels: `#sprint-main` (human <-> Hermes), `#agent-coder` (Hermes -> OpenClaw handoffs),
`#agent-conv` (the two bots converse), `#agent-log` (OpenClaw structured reports),
`#ci-cd` (GitHub Actions results via webhook), `#human-review` (human sign-off before merge),
plus `#qa` (reviewer agent).

The merge actor is always the **human** (`gh pr merge` / GitHub UI). No bot merges to `main`.

---

## Sprint 1 -- Foundation (Laravel 11 + multi-tenancy + auth)

**Plan -- `#sprint-main` (human -> Hermes), 1:56 PM**

> **Chaman:** @Hermes Brain we're ready to start building PulseDesk. Create the Sprint 1 plan for:
> Laravel 11 backend init; multi-tenancy with `BelongsToOrganization` trait and
> `OrganizationScope`; migrations for all core tables; Eloquent models; Laravel Sanctum auth.
> Save the plan to `sprints/sprint-01.md` and assign the first task to @OpenClaw.

Hermes produced the backlog (`sprints/sprint-01.md`) and handed work to OpenClaw.

**Build + report -- OpenClaw**

- Initialized Laravel 11, multi-tenancy foundation, 14 migrations, Eloquent models, Sanctum auth.
- Commits: `64df30b` Laravel 11 init; `1ee07a9` multi-tenancy foundation; `d6c401c` core
  migrations; `424eac0` Eloquent models; `224eab8` Sanctum authentication.

**Definition of done:** project boots, `migrate:fresh` clean, auth endpoints respond,
`OrganizationScope` + `BelongsToOrganization` in place.

---

## Sprint 2 -- Full REST API layer

**Plan / kick-off -- `#sprint-main`, 1:58 PM**

> **Chaman:** @OpenClaw get ready with Sprint 2 and make it done.

**Build + report -- OpenClaw, 2:02 PM (`#agent-log`)**

- Tickets CRUD, ticket comments, attachments, knowledge categories/articles, SLA policies, and
  activity-log endpoints. CRUD + pagination (15/page) + filtering (status/priority/assignee) +
  search + activity logging + tenant isolation + auth rate limiting.
- Commit: `b4b85ce` feat: Sprint 2 -- full REST API layer.

---

## Sprint 3 -- QA hardening (PD-17 .. PD-21) -> PR #1 (human-merged)

**Plan -- backlog posted by Hermes in `#sprint-main`, 3:39 PM**

> **Hermes Brain:** Sprint 3 Backlog -- QA Hardening. 5 scoped issues:
> PD-17 tenant isolation for related resources; PD-18 customer policy hardening;
> PD-19 dashboard + SLA breach math tests; PD-20 notification coverage on assign + reply;
> PD-21 CI Slack webhook + frontend build verification. Full plan written to
> `sprints/sprint-03.md`. Handing PD-17 to OpenClaw in `#agent-coder`.

**Handoff -- `#agent-coder` (Hermes -> OpenClaw), 3:39 PM**

> **Hermes Brain:** @OpenClaw Task PD-17: QA pass -- tenant isolation for related resources.
> A user from Org A must receive 404 on Org B tickets, comments, activity logs, and
> notifications. Open a PR against main on branch `qa/sprint-03`. Do NOT merge.

**Build + report -- `#agent-log` (OpenClaw), 4:02 PM**

> **QA Sprint 3 -- Report**
> **What I Did:** scoped `NotificationController` (index + markAllRead) by `organization_id`;
> expanded `TenantIsolationTest` from 4 to 16 tests (cross-tenant 404 on comments/activity/
> notifications, customer 403 on PATCH/assign/delete, admin delete 204, notification
> counterpart-only, no self-notify); added `DashboardTest` (7 tests: tenant-scoped metrics,
> avg first response, SLA breach rate, per-day grouping); new `SlaPolicyFactory`,
> `NotificationFactory`; fixed 5 JSON int/float assertion mismatches; frontend build verified.
> **Test Results:** 38 passed (84 assertions) -- all green.
> **PR:** https://github.com/DevCadbury/forge2-chaman_kumar/pull/1
> **Needs Human Call:** nothing blocking -- ready for review.

Hermes mirrored progress into `#agent-conv`; a CI note was posted to `#ci-cd`.

**CI -- `#ci-cd`**

- GitHub Actions ran on the PR and on merge; results posted via the Slack webhook, e.g.
  `CI success - CI - main - .../actions/runs/28287303134`.

**Review & merge -- `#human-review` (human)**

- Reviewed PR #1, confirmed CI green and tenant-isolation tests passing, and **merged it to
  `main` myself** (`gh pr merge --squash`, 2026-06-27 10:33 UTC). No bot merge.

---

## Sprint 4 -- Test coverage + CI/CD breadth (PD-23/24/26/27/29) -> PRs #3-#7

**Plan -- `#sprint-main` (human -> Hermes), 4:29 PM**

> **Chaman:** I need more PRs to show active CI/CD. Create branches and open PRs for:
> PD-23 more auth tests; PD-24 ticket filter tests; PD-26 activity log tests;
> PD-27 notification mark-all-read; PD-29 frontend build smoke test.

**Handoff -- `#agent-coder` (Hermes -> OpenClaw)**

Hermes posted five scoped, one-issue-per-PR assignments (PD-23 .. PD-29), each with a branch
name, exact test cases, and "report to #agent-log when done".

**Build + report -- `#agent-log` (OpenClaw), 4:44 PM**

> **5 PRs opened -- all tests passing**
>
> | PR | Branch | Adds |
> |---|---|---|
> | [#3](https://github.com/DevCadbury/forge2-chaman_kumar/pull/3) | `feature/pd-23-auth-tests` | logout revokes token, /me, register validation |
> | [#4](https://github.com/DevCadbury/forge2-chaman_kumar/pull/4) | `feature/pd-24-ticket-filters` | filter by priority/assignee, customer-only scope |
> | [#5](https://github.com/DevCadbury/forge2-chaman_kumar/pull/5) | `feature/pd-26-activity-log` | created/updated/commented logs, tenant-scoped |
> | [#6](https://github.com/DevCadbury/forge2-chaman_kumar/pull/6) | `feature/pd-27-notification-read` | mark-all-read, own-only, listing |
> | [#7](https://github.com/DevCadbury/forge2-chaman_kumar/pull/7) | `feature/pd-29-frontend-smoke` | `dist/index.html` smoke test + CI step |
>
> Full suite: 52 passed (120 assertions). Each branch off clean `main`, independent.

**CI -- `#ci-cd`** (GitHub Actions -> Slack webhook, real run URLs):

```
CI success - CI - 3/merge - .../actions/runs/28287440173
CI success - CI - 4/merge - .../actions/runs/28287455629
CI success - CI - 5/merge - .../actions/runs/28287469154
CI success - CI - 6/merge - .../actions/runs/28287485582
CI success - CI - main    - .../actions/runs/28287489493
```

(`7/merge` first hit a transient Docker Hub `mysql:8` pull timeout; the job was re-run and went green.)

**Review & merge -- `#human-review` (human):** PRs #3-#7 were each reviewed and **merged by the
human** on GitHub after their CI runs went green (plus housekeeping PR #2). PR #7's first run hit
a transient Docker-pull timeout; it was re-run green and then human-merged (2026-06-27 12:21 UTC).

---

## Earlier connectivity / setup exchanges (for completeness)

Before the clean two-bot loop, setup work happened in `#sprint-main` / `#agent-coder`:
connectivity checks ("Hermes online -- channel check"), a `GET /api/health` task (S1-T1) that
OpenClaw implemented and tested (`1 passed`), and project-location alignment so both agents work
inside this repo (`backend/`). These are visible in the Slack export but are not part of the
graded sprint loop above.

---

## Bonus agent -- QA Reviewer (`#qa`)

A third agent (OpenClaw Reviewer, config `agents/openclaw/openclaw-reviewer.json`) ran as a
code-review / QA gate in `#qa`. Its real activity is in `slack-export/qa/`:

- **Reviewed the `GET /api/health` work** (`routes/api.php`, `tests/Feature/HealthTest.php`) and
  posted concrete findings: liveness vs readiness not distinguished (the endpoint returns 200
  even if the DB is down), no DB ping / timestamp / version field, and a thin test (happy-path
  only, no content-type assertion). It proposed a `/health/deep` readiness endpoint that pings
  the database and returns 503 when degraded.
- **Ran a structured project review** across controllers, models/migrations, and tests/routes,
  fanning the deep-dive into parallel sub-reviews and compiling the findings into one report.

These reviews are part of the error -> review -> fix loop and ran before the human signed off in
`#human-review`. (The reviewer shares the OpenClaw gateway's Slack identity; its config, role,
and `#qa` transcript are committed as evidence of genuine participation.)

---

## How this maps to the rubric

- **Orchestration loop:** human goal (`#sprint-main`) -> Hermes plan + scoped handoff
  (`#agent-coder`) -> OpenClaw implements + reports (`#agent-log`) -> CI (`#ci-cd`) -> human
  review + merge (`#human-review`), repeated across Sprints 3 and 4 (and foundation Sprints 1-2).
- **Distinct bots:** `@hermes_brain` and `@openclaw_worker` are separate Slack apps/users
  (different user IDs above).
- **Human is the merge actor:** all PRs #1-#7 merged by the human; no bot ever touches `main`.
- **Evidence cross-checks:** the PR numbers, commit hashes, test counts, and CI run IDs above
  match the GitHub repo, the Actions tab, and the committed `slack-export/`.
