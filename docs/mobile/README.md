# Konnect Nex HRMS Mobile API & Development Documentation

Implementation-driven reference for building the Konnect Nex HRMS mobile application against the current Laravel backend.

**Source of truth:** `routes/api*.php`, controllers, form requests, services, policies, middleware, and config files in this repository.

**Last synced:** 2026-07-27

---

## Purpose

Enable a mobile development team to build and test the HRMS mobile app with minimal backend dependency by documenting:

- Product and platform architecture
- Available REST APIs (and explicit gaps)
- Authentication and multi-tenancy
- Permissions, middleware, and validation
- Response formats and error handling
- Mobile architecture recommendations

---

## Project Overview

### Platform Architecture

Konnect Nex is a multi-tenant SaaS platform. Each **organization** is an isolated tenant. Users may belong to multiple organizations.

| Concern | Implementation |
|---------|----------------|
| **Multi-tenancy** | `TenantContext` set by `set.organization` middleware from session or `X-Organization-Id` |
| **Organization isolation** | All tenant-scoped queries filter by `organization_id` via `TenantContext` |
| **Dynamic RBAC** | `AuthorizationService` + `config/rbac.php` (~300 permissions, 6 default roles) |
| **Module licensing** | `ModuleSubscriptionService` + `module` middleware; HRMS requires `hrms` module license |
| **Dashboard platform** | Widget registry in `config/dashboard.php`; API at `/api/v1/dashboard/*` |
| **Notification platform** | Laravel notifications with `data->organization_id`; inbox is web/session JSON only |
| **Audit logging** | Identity and sensitive HRMS mutations write audit events |
| **ESS platform** | Employee Self-Service at `/hrms/ess/*` (web); partial API mirror for attendance |
| **HRMS architecture** | Controllers → Services → Models; policies per domain (`EmployeePolicy`, `AttendancePolicy`, etc.) |

### How Mobile Fits In

The mobile app is a **Sanctum-authenticated API client** operating in a single organization context per request:

```
Mobile App
  → Bearer token (Sanctum PAT)
  → X-Organization-Id header
  → /api/v1/* endpoints
  → TenantContext + RBAC + module license checks
```

**Current API coverage for HRMS mobile:**

| Domain | API status |
|--------|------------|
| Attendance (today, check-in/out, manager summary) | Available |
| Dashboard widgets (attendance, leave balance, shift) | Available |
| Lookups (employees, departments, branches, shifts) | Available |
| Leave management | **Not available** (web ESS only) |
| Employee profile | **Not available** (web ESS only) |
| Documents / payroll | **Not available** (web ESS only) |
| Notifications inbox | **Not available** (web/session only) |
| Push notifications | **Not implemented** |
| Mobile login / token refresh | **Not available** (PAT via web UI) |

See [api-index.md](./api-index.md) for the complete endpoint catalog and [Development Notes](#development-notes) below.

---

## Documentation Index

| Document | Description |
|----------|-------------|
| [api-index.md](./api-index.md) | Complete endpoint index |
| [mobile-architecture.md](./mobile-architecture.md) | Recommended Flutter architecture |
| [authentication.md](./authentication.md) | Auth model and identity APIs |
| [organization.md](./organization.md) | Multi-tenancy and `X-Organization-Id` |
| [dashboard.md](./dashboard.md) | Dashboard and widget APIs |
| [attendance.md](./attendance.md) | Attendance APIs and business rules |
| [leave-management.md](./leave-management.md) | Leave APIs (gaps documented) |
| [employee-profile.md](./employee-profile.md) | Profile APIs (gaps documented) |
| [employee-directory.md](./employee-directory.md) | Directory and lookup APIs |
| [shifts.md](./shifts.md) | Shift information APIs |
| [holidays.md](./holidays.md) | Holiday APIs (gaps documented) |
| [documents.md](./documents.md) | Document APIs (gaps documented) |
| [notifications.md](./notifications.md) | Notification APIs |
| [tasks-projects.md](./tasks-projects.md) | Task and project APIs |
| [search.md](./search.md) | Universal search / lookup APIs |
| [uploads.md](./uploads.md) | File upload APIs |
| [api-response-format.md](./api-response-format.md) | Response conventions |
| [error-codes.md](./error-codes.md) | HTTP and business errors |
| [permissions.md](./permissions.md) | RBAC and module licensing |
| [security.md](./security.md) | Security guidelines |
| [postman-collection.json](./postman-collection.json) | Postman collection (also at `postman/Konnect Nex-HRMS-Mobile.postman_collection.json`) |
| [openapi.yaml](./openapi.yaml) | OpenAPI 3.1 specification |

---

## API Versioning

| Item | Value |
|------|-------|
| **Current version** | `v1` — all routes under `/api/v1/` |
| **Future strategy** | Introduce `/api/v2/` for breaking changes; maintain `v1` during deprecation window |
| **Breaking change policy** | Document in release notes; minimum 90-day deprecation for removed fields |
| **Deprecation** | `Sunset` header + changelog entry (not yet implemented in codebase) |

---

## Mobile UI Mapping

### Dashboard Screen

```
GET /api/v1/attendance/dashboard          → attendance state, working hours, shift
GET /api/v1/dashboard/widgets/notifications/data  → recent notifications (widget)
GET /api/v1/dashboard/widgets/leave_balance/data  → leave balances (summary)
GET /api/v1/dashboard/quick-actions         → quick action links
```

### Attendance Screen

```
GET  /api/v1/attendance/dashboard           → current state
POST /api/v1/attendance/check-in            → check in
POST /api/v1/attendance/check-out           → check out
```

Attendance history: **no API** — web only at `GET /hrms/ess/attendance`.

### Leave Screen

```
GET  /api/v1/leave/balance                  → NOT AVAILABLE
GET  /api/v1/leave/history                  → NOT AVAILABLE
POST /api/v1/leave                          → NOT AVAILABLE
```

Workaround: `GET /api/v1/dashboard/widgets/leave_balance/data` (summary only).

### Manager Dashboard

```
GET /api/v1/attendance/team-summary         → team KPIs
GET /api/v1/dashboard/widgets/manager_attendance/data
```

### Employee Directory

```
GET /api/v1/lookups/employees?q=            → search (requires hrms.view)
```

Full directory profiles: **web only** at `/hrms/directory`.

### Tasks (if licensed)

```
GET   /api/v1/tasks?assigned_to={user_id}   → my tasks
GET   /api/v1/tasks/{id}                    → task detail
PATCH /api/v1/tasks/{id}                    → update status
POST  /api/v1/tasks/{id}/complete         → complete task
```

Requires `api.access` + `tasks.view` / `tasks.update`.

---

## Development Notes

Observations from the current implementation (no code changes made):

### Missing APIs

- Mobile login, logout, token refresh
- Leave CRUD (apply, withdraw, approve/reject)
- ESS profile GET/PUT
- Employee documents list/download
- Payslip APIs
- Attendance history and corrections
- Holiday calendar
- Employee directory (full profile card)
- Notification inbox (Sanctum JSON)
- Push device registration (FCM/APNs)

### Duplicate / Overlapping APIs

- Attendance data available via both `/api/v1/attendance/dashboard` and `/api/v1/dashboard/widgets/employee_attendance/data`
- Lookups available at `/api/v1/lookups/{entity}` and `/shell/lookups/{entity}` (session)

### Inconsistencies

- No global `{ success, message, data }` envelope — responses vary by endpoint
- `actions.check_in_url` / `check_out_url` in attendance responses point to **web** routes, not API
- API dashboard omits `recent_attendance` and `upcoming_holidays` computed by `AttendanceDashboardService`
- RBAC routes in `routes/api_rbac.php` use `prefix('v1/rbac')` inside the parent `v1` group — may produce `/api/v1/v1/rbac/*` (verify with `php artisan route:list`)
- `leave_balance` widget requires `leave.view`; ESS attendance requires `ess.access` — different permissions for dashboard sections

### Deprecated APIs

None documented for HRMS mobile surface.

### Potential Improvements

- Add dedicated `/api/v1/auth/login` for mobile (Sanctum token issuance)
- Mirror ESS web controllers as JSON APIs under `/api/v1/ess/*`
- Extend attendance dashboard API to include `recent_attendance` and `upcoming_holidays`
- Replace web URLs in `actions` with API paths for mobile clients
- Add Sanctum notification inbox endpoints
- Implement device token registration for push notifications

---

## Related Documentation

- [docs/api/overview.md](../api/overview.md) — API authentication overview
- [docs/api/attendance.md](../api/attendance.md) — Attendance API (abbreviated)
- [docs/api/lookups.md](../api/lookups.md) — Lookup API
- [docs/hrms/employee-dashboard.md](../hrms/employee-dashboard.md) — ESS dashboard architecture
- [docs/hrms/architecture/overview.md](../hrms/architecture/overview.md) — HRMS architecture
