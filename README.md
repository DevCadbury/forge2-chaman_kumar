# PulseDesk — Forge 2 · Edition 1

![CI](https://github.com/forge2-qualifier-chaman/pulsedesk/workflows/CI/badge.svg)

A multi-tenant support-desk SaaS, built by orchestrating **Hermes** (product owner) and
**OpenClaw** (coder) over Slack. Organizations, roles, tickets with threaded conversations,
SLA timers, queues, audit trails, dashboard metrics, and in-app notifications.

## CI/CD
Every push and PR triggers the CI pipeline (`.github/workflows/ci.yml`). Backend tests run against MySQL 8 on PHP 8.2; frontend builds via Node 20. Results post to Slack `#ci-cd`.

## Stack
Laravel 11 · PHP 8.2+ · MySQL 8 · Laravel Sanctum · React 19 · Vite · Tailwind CSS

## EastRouter models used
- Hermes (planning / product owner): `moonshotai/kimi-k2.7-code`
- OpenClaw (coding): `z-ai/glm-5.1`

## Run it (a judge can run these from a fresh clone)

### Backend — Laravel + MySQL
```bash
cd backend
cp .env.example .env            # set DB_* for your MySQL 8 instance
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve               # http://127.0.0.1:8000
```

### Frontend — React + Vite
```bash
cd frontend
cp .env.example .env            # VITE_API_URL=http://127.0.0.1:8000
npm install
npm run dev                     # http://127.0.0.1:5173
```

## Demo logins (from the seeder)
Organization **Acme Inc** (12 tickets):
- `admin@acme.test` / `password` — admin
- `agent1@acme.test` / `password` — agent
- `customer1@acme.test` / `password` — customer

A second org **Globex Corp** (`admin@globex.test` / `password`, 6 tickets) exists to demonstrate
tenant isolation — sign in as each and confirm neither sees the other's data.

## Multi-tenancy in one line
Every tenant-owned row carries an `organization_id`. A global Eloquent scope
(`App\Models\Scopes\OrganizationScope`) derives the tenant from the **authenticated user**
(never from client input) and filters every query. Cross-org access returns `404`.

## Tests
```bash
cd backend
php artisan test
```
Covers auth, ticket CRUD + filters, comment visibility (internal notes hidden from customers),
and tenant isolation. CI runs the same suite against MySQL 8 on every PR.

## Live URL
Runs locally per the steps above.

## Where the evidence lives (everything in this repo)
- `agents/` — real Hermes + OpenClaw configs (secrets redacted to `${ENV}`)
- `agent-log.md` — the human → Hermes → OpenClaw loop
- `sprints/` — one doc per sprint
- `slack-export/` — Slack export or per-channel screenshots
- `evidence/screenshots/` — app, agents-running, and CI screenshots
- `.github/workflows/ci.yml` — CI: install → migrate → test (backend) + build (frontend)
