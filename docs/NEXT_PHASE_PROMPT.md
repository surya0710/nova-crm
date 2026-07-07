# NovaCRM — Development Status

Copy everything below the line into a new Cursor chat to continue building NovaCRM.

---

## Context

NovaCRM is a multi-tenant Laravel CRM. **Phases 1–5 and Tasks are complete.** Continue from the next items below using the existing codebase — do not rebuild what already exists.

### Tech stack
- **Backend:** Laravel 12, PHP 8.2, session auth (Breeze), Sanctum API
- **Frontend:** Blade + Tailwind CSS 3 + Alpine.js + Vite
- **Database:** MySQL (XAMPP) — **never wipe/reset dev data** unless explicitly requested. Use forward-only `php artisan migrate` only.
- **Assets:** Run `npm run build` after CSS/JS changes (or `npm run dev` during development)

### Completed ✅

| Phase | Scope |
|-------|--------|
| **1** | Multi-tenancy, org setup, logo, org switcher, sidebar layout |
| **2** | RBAC — roles, permissions, policies, team management |
| **3** | Leads — CRUD, filters, notes, activity, dashboard stats |
| **4** | Customers, Pipeline, Products, Quotations, Invoices, Payments, Reports |
| **4+** | Per-org SMTP, client email with attachments, industry terminology |
| **5** | Audit logs, notifications, file attachments, global search, REST API (Sanctum) |
| **6** | Tasks & follow-ups — CRUD, due dates, link to leads/customers/deals, dashboard widget |

### Key files
| Area | Path |
|------|------|
| Routes | `routes/web.php`, `routes/api.php`, `routes/auth.php` |
| **Frontend** | **`docs/FRONTEND.md`** — Vite, Tailwind, Alpine, layouts, components, UI patterns |
| Tenant | `app/Services/TenantContext.php`, `app/Models/Concerns/BelongsToOrganization.php` |
| RBAC | `config/rbac.php`, `app/Services/OrganizationRoleService.php` |
| Tasks | `app/Models/Task.php`, `app/Http/Controllers/TaskController.php`, `config/tasks.php` |
| Email | `app/Services/OrganizationMailer.php`, `config/organization_mail.php` |
| Layout | `resources/views/layouts/app.blade.php`, `resources/views/layouts/sidebar.blade.php` |

---

## Suggested next work (pick one)

### A — Lead CSV import
- Upload CSV on leads index
- Map columns, validate, bulk create with `source = import`
- Feature tests

### B — Expand REST API
- Endpoints for quotations, invoices, payments, tasks
- `X-Organization-Id` header in API tokens UI
- Optional webhooks

### C — Calendar
- Month/week view of tasks and follow-ups
- iCal export (optional)

### D — Production polish
- Super Admin panel for all orgs
- Data export (CSV) from reports
- Custom fields UI for leads
- Deployment documentation

### E — Automation
- Rules: e.g. when lead status → Qualified, create task for assignee
- Email reminders for overdue tasks

---

## Design conventions (follow strictly)

- **Layout:** Dark sidebar, white content cards, indigo/violet accents
- **Settings pages:** Tabbed UI (see `resources/views/organizations/edit.blade.php`)
- **Components:** Reuse `x-organization-logo`, `x-flash-messages`, `x-sidebar-link`, `x-tasks-panel`
- **Org scoping:** `BelongsToOrganization` + `OrganizationScope` on all tenant models
- **RBAC:** `hasPermission('module.action')` + policies on controllers
- **Email:** Always via `OrganizationMailer`, never global `.env` mail
- **Minimal scope:** Smallest correct diff; match existing code style

---

## Verification checklist (after each change)

- [ ] `php artisan migrate` (no fresh/refresh)
- [ ] `npm run build` if views/CSS changed
- [ ] `php artisan test`
- [ ] Manual check: existing org data still intact
