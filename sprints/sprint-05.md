# Sprint 5 — Ticket Reopen (PD-31)

**Goal:** Allow staff (admin/agent) to reopen a resolved ticket.  
**Branch:** `feature/pd-31-ticket-reopen`  
**Test filter:** `php artisan test --filter=TicketReopen`

---

## Issue 1 — Route + Controller `reopen()` method

| | |
|---|---|
| **Objective** | Add `PATCH /api/tickets/{ticket}/reopen` endpoint and the `reopen()` controller method. |
| **Files** | `backend/routes/api.php`, `backend/app/Http/Controllers/Api/TicketController.php` |
| **Acceptance Criteria** | 1. Route registered inside `auth:sanctum` group: `Route::patch('/tickets/{ticket}/reopen', [TicketController::class, 'reopen'])`. 2. `TicketController::reopen(Ticket $ticket)` exists, sets `status = TicketStatus::Open`, clears `resolved_at` to `null`, saves, returns `TicketResource`. 3. Uses `$this->authorize('reopen', $ticket)`. |
| **Test Command** | `php artisan test --filter=TicketReopen` |

---

## Issue 2 — TicketPolicy `reopen()` authorization

| | |
|---|---|
| **Objective** | Enforce that only staff can reopen; customers receive 403. |
| **Files** | `backend/app/Policies/TicketPolicy.php` |
| **Acceptance Criteria** | 1. `reopen(User $user, Ticket $ticket): bool` returns `$user->isStaff()`. 2. Calling the endpoint as a customer returns `403 Forbidden`. |
| **Test Command** | `php artisan test --filter=TicketReopen` |

---

## Issue 3 — ActivityLog entry (`action="reopened"`)

| | |
|---|---|
| **Objective** | Record an activity log when a ticket is reopened. |
| **Files** | `backend/app/Http/Controllers/Api/TicketController.php`, `backend/app/Services/ActivityLogger.php` (read-only, confirm interface) |
| **Acceptance Criteria** | 1. Inside `reopen()`, after save, call `$this->activity->record($ticket, 'reopened')`. 2. Database row exists with `action = 'reopened'` and `ticket_id` matching the ticket. |
| **Test Command** | `php artisan test --filter=TicketReopen` |

---

## Issue 4 — Feature tests (`TicketReopenTest.php`)

| | |
|---|---|
| **Objective** | Cover the full reopen flow with automated tests. |
| **Files** | `backend/tests/Feature/TicketReopenTest.php` |
| **Acceptance Criteria** | 1. `staff_can_reopen_resolved_ticket` — asserts 200, `status = 'open'`, `resolved_at = null`, activity log exists. 2. `customer_cannot_reopen_ticket` — asserts 403. 3. `unauthenticated_user_cannot_reopen` — asserts 401. 4. `reopen_creates_activity_log` — asserts `ActivityLog` row with `action = 'reopened'`. 5. All tests pass with `php artisan test --filter=TicketReopen`. |
| **Test Command** | `php artisan test --filter=TicketReopen` |

---

## Sprint Definition of Done

- [ ] All 4 issues implemented and passing tests  
- [ ] `php artisan test --filter=TicketReopen` is green  
- [ ] PR opened against `main` with branch `feature/pd-31-ticket-reopen`  
- [ ] Human merges on GitHub (Hermes does NOT merge)
