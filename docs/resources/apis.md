# Resource Planning REST API

## Purpose
Reference for Resource Planning API endpoints as registered in `routes/api.php`.

## Base Path
`/api/v1`

## Authentication and Tenancy
- **Auth:** Sanctum bearer token (`auth:sanctum`)
- **Organization:** `X-Organization-Id` header via organization middleware
- **API gate:** `permission:api.access` middleware group
- **Permissions:** `resources.view`, `resources.allocate`, `resources.manage` (and `resources.export` where applicable) enforced by policies

## Resource calendars
`Route::apiResource('resource-calendars', …)` — parameter `{resource_calendar}`.

| Method | Path | Permission | Description |
| --- | --- | --- | --- |
| GET | `/resource-calendars` | `resources.view` | Paginated list; optional `employee_id`, `per_page` |
| POST | `/resource-calendars` | `resources.manage` | Create calendar |
| GET | `/resource-calendars/{resource_calendar}` | `resources.view` | Show |
| PUT/PATCH | `/resource-calendars/{resource_calendar}` | `resources.manage` | Update |
| DELETE | `/resource-calendars/{resource_calendar}` | `resources.manage` | Delete |

**Create body (typical):** `employee_id`, `working_hours_per_day`, `working_days[]`, optional `timezone`, `effective_from`, optional `effective_to`. Effective windows must not overlap for the same employee.

## Resource allocations
`Route::apiResource('resource-allocations', …)` — parameter `{resource_allocation}`.

| Method | Path | Permission | Description |
| --- | --- | --- | --- |
| GET | `/resource-allocations` | `resources.view` | Paginated list |
| POST | `/resource-allocations` | `resources.allocate` or `resources.manage` | Create |
| GET | `/resource-allocations/{resource_allocation}` | `resources.view` | Show |
| PUT/PATCH | `/resource-allocations/{resource_allocation}` | `resources.allocate` or `resources.manage` | Update |
| DELETE | `/resource-allocations/{resource_allocation}` | `resources.allocate` or `resources.manage` | Release (delete) |

**Index filters:** `search`, `employee_id`, `project_id`, `task_id`, `allocation_type`, `from`, `to`, `page`, `per_page` (max 100, default 15).

**Create/update body (typical):** `employee_id`, `allocation_type`, `allocation_percentage` (1…`max_allocation_percentage`), `planned_start_date`, `planned_end_date`, optional `project_id`, `task_id`, `planned_hours`, `notes`. Types `project` and `task` require `project_id`; type `task` also requires an open task belonging to that project. Metadata entity `resource_allocation` values are accepted when defined.

## Workload
| Method | Path | Permission | Description |
| --- | --- | --- | --- |
| GET | `/workload/employees/{employee}` | `resources.view` | Employee workload for `from`/`to` (defaults: current month) |
| GET | `/workload/team` | `resources.view` | Team workloads (defaults: current week) |
| POST | `/workload/snapshots` | `resources.manage` | Persist snapshot(s) for `date` (optional `employee_id`; omit for whole team) |

Employee must belong to the current organization (else 404).

## Capacity
| Method | Path | Permission | Description |
| --- | --- | --- | --- |
| GET | `/capacity/forecast` | `resources.view` | Forecast for org or optional `employee_id`; query `from`/`to` (default: today → +`capacity_risk_days`) |
| GET | `/capacity/risks` | `resources.view` | Upcoming overallocation risks; optional `days` (1–365) |

## Dashboard widget data
Resource widgets use the shared dashboard API (`GET /api/v1/dashboard/widgets/{widgetKey}/data`), not dedicated `/resources/...` paths. Keys include `team_workload`, `resource_availability`, `overallocated_employees`, `upcoming_capacity_risks`.

## Multi-Tenancy
All queries are organization-scoped. Cross-tenant resource IDs return 404.

## Related Documentation
See [overview](overview.md), [workload-calculations](workload-calculations.md), and [developer-guide](developer-guide.md).
