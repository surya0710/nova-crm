# Permissions

RBAC permissions and module licensing for HRMS mobile APIs.

**Source:** `config/rbac.php`, `EnsureUserHasPermission` middleware, `AuthorizationService`

---

## How Permissions Work

1. User assigned roles in organization (`organization-owner`, `employee`, `manager`, `hr`, etc.)
2. Roles map to permission slugs in `config/rbac.php`
3. `AuthorizationService` caches effective permissions (300s TTL)
4. Organization owners (`organization-owner`, `organization-administrator`) get all permissions
5. Super-admins bypass all checks
6. Route middleware: `permission:{slug}`

Check effective permissions:

```
GET /api/v1/rbac/authorization
GET /api/v1/rbac/authorization?permission=ess.access
```

---

## HRMS Mobile Permissions

| Slug | Group | Description | Endpoints |
|------|-------|-------------|-----------|
| `ess.access` | Employee Self-Service | Access ESS portal | Attendance dashboard, check-in/out, attendance widgets |
| `leave.view` | Leave | View leave types, balances, applications | Leave balance widget |
| `leave.manage` | Leave | Manage leave configuration | HR admin (web) |
| `leave.approve` | Leave | Approve/reject leave | Manager (web) |
| `attendance.view` | Attendance | View attendance records | HR admin (web) |
| `attendance.manage` | Attendance | Manage attendance and shifts | HR admin (web) |
| `attendance.correct` | Attendance | Review corrections | HR admin (web) |
| `manager.dashboard` | HRMS | View manager team dashboard | Team summary, manager widget |
| `employee.directory` | HR Operations | View employee directory | Web directory (not API) |
| `hrms.view` | HRMS | View HRMS data | Lookup APIs |
| `hrms.manage` | HRMS | Manage HRMS | Identity admin APIs |
| `dashboard.view` | Dashboard | View dashboard | Dashboard APIs, widgets |
| `dashboard.customize` | Dashboard | Customize dashboard | Preferences mutations |
| `api.access` | API | Access REST API | Tasks, projects, CRM APIs |
| `api.tokens` | API | Create/revoke API tokens | Web `/api-tokens` |
| `rbac.view` | RBAC | View authorization | `/rbac/authorization` |

---

## Default Role Permissions (HRMS-relevant)

### `employee` role

```
ess.access
leave.view
employee.directory
organization.calendar
dashboard.view
tasks.view (limited)
projects.view (limited)
```

Does **not** include: `api.access`, `hrms.view`, `manager.dashboard`

### `manager` role

```
ess.access
manager.dashboard
leave.view, leave.approve
attendance.view, attendance.correct
employee.directory
dashboard.view
api.access (varies)
```

### `hr` role

```
hrms.view, hrms.manage
attendance.view, attendance.manage, attendance.correct
leave.view, leave.manage, leave.approve
ess.access
employee.directory
api.access
exports.*, imports.*
```

### `organization-owner` role

`*` (all permissions)

---

## Module Licensing

From `config/modules.php` and `ModuleSubscriptionService`:

| Module key | HRMS APIs requiring license |
|------------|----------------------------|
| `hrms` | Attendance, HRMS widgets, employee lookups |
| `notifications` | Notifications widget |
| `projects` | Tasks, projects APIs |
| `common` | Welcome widget (no license) |

Check: org plan + `organization_modules` table assignments.

Denied → 403 `"Module not licensed."`

Web routes use `module` middleware. API routes check license in widget/lookup services.

---

## Organization Scope

All permissions are evaluated within current organization (`TenantContext`).

User must be member of organization sent in `X-Organization-Id`.

---

## Ownership Rules

| Resource | Rule |
|----------|------|
| Attendance clock | `EmployeePolicy::clock` — own employee only |
| ESS profile | `EmployeePolicy::viewOwn` / `updateOwn` |
| Leave apply | `EmployeePolicy::applyLeave` — own employee |
| Leave withdraw | `LeaveApplicationPolicy::withdrawOwn` — own application |
| Task view | `TaskPolicy::view` — project membership or assignee |
| Documents | `EmployeeDocumentPolicy` — own documents (web) |

---

## Permission vs Middleware Matrix

| Endpoint | Middleware permission | Additional policy |
|----------|----------------------|-------------------|
| `GET /attendance/dashboard` | `ess.access` | `requireEmployee` |
| `POST /attendance/check-in` | `ess.access` | `EmployeePolicy::clock` |
| `GET /attendance/team-summary` | `manager.dashboard` | Direct reports scope |
| `GET /lookups/employees` | — | `hrms.view` in service |
| `GET /dashboard/widgets/*/data` | `dashboard.view` | Widget `permission_slug` |
| `GET /tasks` | `api.access` | `TaskPolicy` per record |

---

## Mobile Permission Check Flow

```
App launch
  → GET /rbac/authorization
  → Cache permissions locally
  → Check ess.access before showing HRMS tab
  → Check hrms module via feature flags (attempt API call, handle 403)
  → Check leave.view for leave balance widget
  → Check manager.dashboard for manager features
  → Check api.access before tasks/projects
```

---

## Dynamic RBAC

Additional permissions may be defined in `config/dynamic_rbac.php` and synced via `OrganizationRoleService::seedPermissions()`.

Custom roles can be created per organization in admin UI.

---

## API Access Gate

Most non-HRMS REST endpoints require `api.access` in addition to domain permissions.

HRMS attendance routes do **not** require `api.access` — only `ess.access` or `manager.dashboard`.

This means employees can use attendance APIs without `api.access` if they have a valid token and `ess.access`.
