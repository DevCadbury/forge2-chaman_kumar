# Architecture — PulseDesk

## Overview
PulseDesk is a REST API (Laravel 11) consumed by a React 19 SPA. Authentication is token-based
via Laravel Sanctum. Data is isolated per tenant (organization) at the query layer.

## Multi-tenancy approach
Shared database, row-level isolation by `organization_id`.

- Every tenant-owned table (`tickets`, `comments`, `sla_policies`, `activity_logs`,
  `app_notifications`) has an `organization_id` foreign key. `users` carry it too.
- `App\Support\TenantContext` resolves the current tenant from the **authenticated user**
  (`auth()->user()->organization_id`). The client never supplies the org id.
- The `App\Models\Concerns\BelongsToOrganization` trait:
  - adds a global scope (`OrganizationScope`) that appends
    `where organization_id = <current tenant>` to every query, and
  - auto-fills `organization_id` on create from the tenant context.
- Route-model binding therefore returns `404` for any record outside the caller's org, so
  cross-tenant reads/writes/deletes fail closed.
- Role checks (`admin` / `agent` / `customer`) layer on top via `TicketPolicy`.

## Data model
| Model | Key fields |
| --- | --- |
| Organization | id, name, slug |
| User | id, organization_id, name, email, password, role |
| Ticket | id, organization_id, subject, description, status, priority, requester_id, assignee_id, tags, first_responded_at, resolved_at |
| Comment | id, organization_id, ticket_id, user_id, body, is_internal |
| SlaPolicy | id, organization_id, priority, response_minutes, resolution_minutes |
| ActivityLog | id, organization_id, ticket_id, user_id, action, meta, created_at |
| Notification (`app_notifications`) | id, organization_id, user_id, ticket_id, type, data, read_at |

Enums: `TicketStatus` (open/pending/resolved/closed), `TicketPriority` (low/medium/high/urgent),
`UserRole` (admin/agent/customer).

## API routes (`backend/routes/api.php`)
| Method | Path | Auth | Notes |
| --- | --- | --- | --- |
| POST | /api/register | — | creates org + admin, returns token |
| POST | /api/login | — | returns Sanctum token |
| POST | /api/logout | token | revokes current token |
| GET | /api/me | token | current user |
| GET | /api/tickets | token | tenant-scoped; `?status=&priority=&assignee=&q=`; customers see only their own |
| POST | /api/tickets | token | requester = caller |
| GET | /api/tickets/{id} | token | includes SLA summary |
| PATCH | /api/tickets/{id} | staff | status / priority / subject / tags |
| DELETE | /api/tickets/{id} | admin | |
| POST | /api/tickets/{id}/assign | staff | claim / reassign / unassign |
| GET | /api/tickets/{id}/comments | token | internal notes hidden from customers |
| POST | /api/tickets/{id}/comments | token | `is_internal` requires staff |
| GET | /api/tickets/{id}/activity | token | audit trail |
| GET | /api/agents | token | assignable staff in the org |
| GET | /api/dashboard/metrics | token | counts, avg first response, SLA breach rate, per-day |
| GET | /api/notifications | token | current user's notifications |
| PATCH | /api/notifications/{id}/read | token | |
| POST | /api/notifications/read-all | token | |

## Key decisions
- **Token auth over cookie/SPA mode** — keeps the API stateless and the React client simple
  (Bearer token in `localStorage`, attached by `src/api/client.js`).
- **TenantContext indirection** — lets the global scope work in HTTP (from auth), and stays inert
  in console/seeding where there is no authenticated user, so seeders can write across orgs.
- **Custom `app_notifications` table** — a small purpose-built table instead of Laravel's generic
  notifications, scoped per tenant like everything else.
- **SLA computed on read** — `SlaService` derives response/resolution due + breach state from the
  per-priority `SlaPolicy`, so no background jobs are required for the timers.

## Frontend structure
- `src/api/client.js` — fetch wrapper, token handling, typed errors.
- `src/context/AuthContext.jsx` — session bootstrap, login/register/logout.
- `src/components/` — Layout, NotificationBell, SlaBadge, Modal, Badge, Avatar, Spinner.
- `src/pages/` — Login, Register, TicketsList (filters + search + create), TicketDetail
  (conversation, status/priority/assignee, SLA, activity), Dashboard (metrics + charts).
