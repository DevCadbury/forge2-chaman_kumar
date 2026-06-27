# Deployment — PulseDesk

- **Backend (Laravel API)** → Render (Docker)
- **Frontend (React SPA)** → Vercel
- **Database (MySQL 8)** → an external managed MySQL (Render has no managed MySQL)

```
Vercel (React)  ──HTTPS──▶  Render (Laravel API)  ──▶  Managed MySQL 8
```

---

## 1 · Database (do this first)
Render only offers managed PostgreSQL, and the build requires **MySQL 8**, so use a free managed
MySQL from one of: Aiven, Clever Cloud, Railway, or filess.io. Create a MySQL 8 database and copy:
`host`, `port` (usually 3306), `database`, `username`, `password`.

You'll paste these into Render as `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.

---

## 2 · Backend on Render (Docker)

Files already in the repo: `backend/Dockerfile`, `backend/docker/000-default.conf`,
`backend/docker/entrypoint.sh`, `backend/.dockerignore`, and a root `render.yaml` blueprint.

The image: builds Composer deps (no-dev), runs PHP 8.3 + Apache serving `public/`, and on every
boot runs `config:cache`, `route:cache`, and `php artisan migrate --force`.

### Option A — Blueprint (uses render.yaml)
1. Render → **New → Blueprint** → connect the repo. It reads `render.yaml` and creates the
   `pulsedesk-api` web service (root dir `backend`, Docker).
2. Fill the `sync: false` env vars when prompted (see list below).

### Option B — Manual web service
1. Render → **New → Web Service** → connect the repo.
2. **Root Directory:** `backend`  ·  **Runtime:** Docker (auto-detected from `Dockerfile`).
3. **Instance type:** Free. No build/start command needed.

### Environment variables (Render → Environment)
```
APP_NAME=PulseDesk
APP_ENV=production
APP_DEBUG=false
APP_KEY=            # generate locally: `php artisan key:generate --show`  → paste the base64: value
APP_URL=https://pulsedesk-api.onrender.com      # your Render URL (set after first deploy)
FRONTEND_URL=https://<your-app>.vercel.app       # your Vercel URL (set after step 3)
LOG_CHANNEL=stderr
DB_CONNECTION=mysql
DB_HOST=...        # from step 1
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
RUN_SEED=true      # set true for the FIRST deploy to seed demo data, then set back to false
```

> `APP_KEY` is required. Generate it once locally with `php artisan key:generate --show` and paste
> the whole `base64:...` string. Never commit it.

4. Deploy. First boot migrates (and seeds if `RUN_SEED=true`). Your API is at
   `https://pulsedesk-api.onrender.com`. Test: `GET /api/login` should respond (405/422 is fine —
   it proves the app is up). After it's up, set `RUN_SEED=false` and redeploy.

---

## 3 · Frontend on Vercel

File already in the repo: `frontend/vercel.json` (Vite + SPA rewrites).

1. Vercel → **Add New → Project** → import the repo.
2. **Root Directory:** `frontend`  ·  Framework preset: **Vite** (auto).
3. **Environment Variables:**
   ```
   VITE_API_URL = https://pulsedesk-api.onrender.com
   ```
   (Vite inlines this at build time, so set it before the build / redeploy after changing it.)
4. Deploy. You get `https://<your-app>.vercel.app`.
5. Go back to Render and set `FRONTEND_URL` to that Vercel URL, then redeploy the API so CORS
   allows it. (CORS also allows any `*.vercel.app` origin via `backend/config/cors.php`.)

---

## 4 · Verify end to end
1. Open the Vercel URL → log in with `admin@acme.test` / `password` (seeded).
2. Create a ticket, reply, change status, open the dashboard.
3. Screenshot the running app into `evidence/screenshots/` and put both live URLs in `README.md`
   and `SUBMISSION.md`.

---

## 5 · Auto-deploy
Both platforms redeploy on every push to `main`:
- Render rebuilds the Docker image and runs migrations.
- Vercel rebuilds the SPA.

So the CI/CD story is: GitHub Actions (tests) → merge to `main` → Render + Vercel auto-deploy.

## Notes / gotchas
- **Render free tier sleeps** after inactivity; the first request after idle is slow. Fine for a demo.
- **MySQL host must not be `localhost`** — use the managed DB host from step 1.
- If migrations fail on boot, check the DB env vars and that the DB accepts external connections.
- `php artisan key:generate --show` locally to mint `APP_KEY`; set it in Render, not in git.
