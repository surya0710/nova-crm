# HRMS Platform Runtime Contract

**Phase:** 10.1.1 Foundation  
**Status:** Frozen for foundation scaffolding (no business runtime yet)

## Purpose

This document defines architectural boundaries for the Human Resource Management System (HRMS) platform within NovaCRM. Phase 10.1.1 establishes schema, configuration, RBAC, policies, models, service skeletons, navigation, and placeholder dashboards only.

## Architecture

```text
Controllers → Form Requests → Hrms*Service → Models (organization_id)
```

- Controllers remain thin HTTP adapters.
- Services under `app/Services/Hrms/` are the future write authorities.
- Models use `BelongsToOrganization` and `Auditable` where appropriate.
- No repository pattern, DDD, CQRS, event sourcing, or generic base services.

## Tenancy

- Every HRMS table includes `organization_id`.
- Eloquent models apply `OrganizationScope` via `BelongsToOrganization`.
- Composite unique keys `(organization_id, id)` support tenant-safe foreign keys.
- Cross-organization access must return empty results or 404 under tenant context.

## Employee identity model

- `employees` is the HR master record (separate from CRM `users`).
- `employees.user_id` is **nullable** and unique per organization when set.
- Linking a User enables Employee Self-Service (ESS) portal access.
- Staff may exist without a portal login until `user_id` is assigned.

## ESS linkage

- Permission: `ess.access`
- Routes: `/ess` (placeholder dashboard in 10.1.1)
- `App\Services\Hrms\EssContext` resolves the current tenant Employee for an authenticated User.
- ESS must never expose another employee's records (enforced in later phases).

## Service ownership

| Service | Future responsibility |
| --- | --- |
| `EmployeeService` | Employee master + profile writes |
| `BranchService` / `DepartmentService` / `DesignationService` / `TeamService` | Organization structure |
| `EmployeeDocumentService` | Document upload, versioning, expiry, download authz |
| `AttendanceService` | Clock in/out, corrections, shifts, OT |
| `LeaveService` | Leave types, balances, applications, approval steps |
| `HrmsDashboardService` | HR / manager / employee widgets |
| `EssContext` | Current-employee resolution for ESS |

Phase 10.1.1 services contain constructor injection only (no business methods beyond ESS resolution).

## Audit

- Reuse `Auditable` + `AuditLogger`.
- No custom HRMS audit subsystem.

## Future workflow integration

Placeholder trigger keys live in `config/hrms.php` under `workflow_triggers`.

They are **not** registered in `config/workflows.php` and have **no** listeners in Phase 10.1.1.

Intended later triggers include:

- `employee.created`, `employee.exited`, `employee.probation_ending`
- `leave.submitted`, `leave.approved`, `leave.rejected`, `leave.cancelled`
- `attendance.correction_submitted`
- `employee_document.expiring`

HR approval state machines remain in Leave/Attendance services. Workflow handlers must call those services — never embed HR business rules inside the Workflow Platform.

## Module boundaries

| In HRMS | Out of scope (other platforms / later phases) |
| --- | --- |
| Employees, org structure, documents, attendance, leave, ESS | Payroll, performance, recruitment, L&D |
| RBAC permissions `hrms.*`, `attendance.*`, `leave.*`, `ess.*` | Assignment strategy changes |
| Catalogs in `config/hrms.php` | Biometrics, geo-fencing, mobile apps |

## Configuration catalogs

All status/type enums and defaults are defined in `config/hrms.php`:

- employment statuses / types
- attendance & leave statuses
- default leave types
- document categories & identity types
- shift presets
- probation defaults
- working / weekend days

Application code must read these catalogs rather than hardcoding values.

## RBAC summary

| Permission | Intent |
| --- | --- |
| `hrms.view` / `create` / `update` / `manage` | HR administration |
| `hrms.documents.manage` | Employee documents |
| `attendance.view` / `manage` / `correct` | Attendance |
| `leave.view` / `manage` / `approve` | Leave |
| `ess.access` | Self-service portal |

Default role grants (additive sync):

- **hr** — full HRMS + ESS
- **manager** — view HRMS, attendance view/correct, leave view/approve, ESS
- **employee** — ESS access
- **organization-owner** — all (via `*`)
