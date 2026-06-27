# Sprint 01 — Foundation + Must Core

Goal: Stand up the multi-tenant foundation and the core ticketing flow end to end.
Models: Hermes=deepseek/deepseek-v4-pro, OpenClaw=z-ai/glm-5.1

## Issues
- [x] #1 Scaffold Laravel 11 + Sanctum + MySQL config + `.env.example`
- [x] #2 Organizations + tenant fields on users; roles (admin/agent/customer)
- [x] #3 Multi-tenancy: `OrganizationScope` global scope + `BelongsToOrganization` trait + `TenantContext`
- [x] #4 Auth endpoints (register → org + admin, login, logout, me)
- [x] #5 Ticket model + CRUD API + filters (status/priority/assignee) + search
- [x] #6 Threaded comments (public reply + internal note) with role visibility
- [x] #7 `TicketPolicy` for tenant + role authorization
- [x] #8 Seeder: Acme (admin, 2 agents, 2 customers, 12 tickets) + Globex for isolation
- [x] #9 Feature tests: auth, ticket CRUD/filters, comment visibility, tenant isolation

## Outcome
- Shipped: full Must-tier API + 19 passing feature tests (tenant isolation proven).
- Slipped / moved to next sprint: SLA timers, dashboard, notifications, React UI.
- PRs: merged to main after review (human).
