# API Index — HRMS Mobile

Base URL: `{host}/api/v1`

Global headers for authenticated requests:

```http
Authorization: Bearer {sanctum_token}
Accept: application/json
Content-Type: application/json
X-Organization-Id: {organization_id}
```

Global middleware stack (most authenticated routes):

`auth:sanctum` → `throttle:api` (120/min) → `set.organization` → `ensure.organization` → `organization.api`

---

## Authentication & Identity

| Method | Endpoint | Permission | Status |
|--------|----------|------------|--------|
| POST | `/invitations/activate` | Public (`throttle:10,1`) | Available |
| POST | `/identity/employees/{employee}/login-account` | `hrms.manage` | Admin only |
| POST | `/identity/users/{user}/invitations` | `users.manage` or `hrms.manage` | Admin only |
| GET | `/identity/users/{user}/invitation-status` | `users.view` or `hrms.view` | Admin only |
| POST | `/identity/users/{user}/portal/enable` | `users.manage` or `hrms.manage` | Admin only |
| POST | `/identity/users/{user}/portal/disable` | `users.manage` or `hrms.manage` | Admin only |
| POST | `/identity/users/{user}/password-reset` | `users.manage` or `hrms.manage` | Admin only |

**Not available:** login, logout, refresh token, forgot password, change password, profile (web only).

---

## Organization & RBAC

| Method | Endpoint | Permission | Notes |
|--------|----------|------------|-------|
| GET | `/rbac/authorization` | `rbac.view` | Effective permissions |
| GET | `/rbac/authorization?permission={slug}` | `rbac.view` | Single permission check |
| GET | `/rbac/permissions` | `rbac.view` | List permissions |
| GET | `/rbac/roles` | `rbac.view` | List roles |

> **Note:** Verify actual URI — `api_rbac.php` may register under `/v1/v1/rbac` due to nested prefix.

---

## Dashboard

| Method | Endpoint | Permission |
|--------|----------|------------|
| GET | `/dashboard` | `dashboard.view` |
| GET | `/dashboard/workspace` | `dashboard.view` |
| GET | `/dashboard/widgets` | `dashboard.view` |
| GET | `/dashboard/widgets/{widgetKey}/data` | `dashboard.view` |
| POST | `/dashboard/widgets/{widget}/refresh` | `dashboard.view` |
| GET | `/dashboard/quick-actions` | `dashboard.view` |
| GET | `/dashboard/recent-activities` | `dashboard.view` |
| GET | `/dashboard/preferences` | `dashboard.view` |
| POST | `/dashboard/preferences` | `dashboard.customize` |
| DELETE | `/dashboard/preferences` | `dashboard.customize` |

### HRMS Widget Keys

| Widget key | Permission | Module license |
|------------|------------|----------------|
| `mark_attendance` | `ess.access` | `hrms` |
| `employee_attendance` | `ess.access` | `hrms` |
| `working_hours` | `ess.access` | `hrms` |
| `shift_information` | `ess.access` | `hrms` |
| `leave_balance` | `leave.view` | `hrms` |
| `manager_attendance` | `manager.dashboard` | `hrms` |
| `notifications` | none | `notifications` |

---

## Attendance

| Method | Endpoint | Permission |
|--------|----------|------------|
| GET | `/attendance/dashboard` | `ess.access` |
| POST | `/attendance/check-in` | `ess.access` |
| POST | `/attendance/check-out` | `ess.access` |
| GET | `/attendance/team-summary` | `manager.dashboard` |

---

## Lookups (Search)

| Method | Endpoint | Permission | Module |
|--------|----------|------------|--------|
| GET | `/lookups/users` | none | — |
| GET | `/lookups/employees` | `hrms.view` | `hrms` |
| GET | `/lookups/departments` | `hrms.view` | `hrms` |
| GET | `/lookups/designations` | `hrms.view` | `hrms` |
| GET | `/lookups/branches` | `hrms.view` | `hrms` |
| GET | `/lookups/shifts` | `hrms.view` | `hrms` |

Query: `?q=&page=1&per_page=20&id={record_id}`

---

## Leave Management

| Method | Endpoint | Status |
|--------|----------|--------|
| GET | `/leave/balance` | **Not implemented** |
| GET | `/leave/types` | **Not implemented** |
| POST | `/leave` | **Not implemented** |
| GET | `/leave/history` | **Not implemented** |
| POST | `/leave/{id}/approve` | **Not implemented** |

Workaround: `GET /dashboard/widgets/leave_balance/data`

Web reference: `/hrms/ess/leave` (`EssLeaveController`)

---

## Employee Profile

| Method | Endpoint | Status |
|--------|----------|--------|
| GET | `/ess/profile` | **Not implemented** |
| PUT | `/ess/profile` | **Not implemented** |

Web reference: `/hrms/ess/profile` (`EssProfileController`)

---

## Employee Directory

| Method | Endpoint | Status |
|--------|----------|--------|
| GET | `/directory/employees` | **Not implemented** |
| GET | `/lookups/employees` | Available (search/picker) |

Web reference: `/hrms/directory` (`EmployeeDirectoryController`, permission `employee.directory`)

---

## Shifts & Holidays

| Method | Endpoint | Status |
|--------|----------|--------|
| GET | `/shifts/current` | **Not implemented** |
| GET | `/holidays` | **Not implemented** |
| GET | `/holidays/upcoming` | **Not implemented** |

Partial data in `GET /attendance/dashboard` → `shift_info`. Holidays computed in service but not exposed via API.

---

## Documents

| Method | Endpoint | Status |
|--------|----------|--------|
| GET | `/ess/documents` | **Not implemented** |
| GET | `/ess/documents/{id}/download` | **Not implemented** |

Web reference: `/hrms/ess/documents` (`EssDocumentController`)

---

## Notifications

| Method | Endpoint | Status |
|--------|----------|--------|
| GET | `/notifications` | **Not implemented** (Sanctum) |
| POST | `/notifications/{id}/read` | **Not implemented** |
| GET | `/dashboard/widgets/notifications/data` | Available (widget) |
| GET/PUT | `/notification-preferences` | Available (projects scope) |

Web/session: `GET /shell/notifications`

---

## Tasks & Projects

Requires `api.access` plus domain permissions.

| Method | Endpoint | Permission (typical) |
|--------|----------|---------------------|
| GET | `/tasks` | `tasks.view` |
| GET | `/tasks/{task}` | `tasks.view` |
| PATCH | `/tasks/{task}` | `tasks.update` |
| POST | `/tasks/{task}/complete` | `tasks.update` |
| GET | `/projects` | `projects.view` |
| GET | `/projects/{project}` | `projects.view` |

---

## File Uploads

| Method | Endpoint | Scope |
|--------|----------|-------|
| POST | `/tasks/{task}/attachments` | Task attachments only |

No HRMS employee document upload API.

---

## Exports (Admin)

| Method | Endpoint | Permission |
|--------|----------|------------|
| POST | `/exports/sessions` | `exports.create` |
| GET | `/exports/sessions/{session}` | `exports.view` |

Not intended for mobile ESS use.
