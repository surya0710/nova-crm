# Resource Planning Developer Guide

## Purpose
Guide for extending Resource Planning with services, events, permissions, calendar resolution, and leave reuse.

## Key paths
| Area | Path |
| --- | --- |
| Config | `config/resources.php` |
| Models | `ResourceCalendar`, `ResourceAllocation`, `WorkloadSnapshot` |
| Services | `ResourceCalendarService`, `ResourceAllocationService`, `WorkloadService`, `CapacityPlanningService` |
| Events | `ResourceAllocated`, `ResourceAllocationUpdated`, `ResourceReleased`, `CapacityExceeded`, `OverallocationDetected` |
| Policies | `ResourceCalendarPolicy`, `ResourceAllocationPolicy`, `WorkloadPolicy` |
| Widgets | `TeamWorkloadWidgetProvider`, `ResourceAvailabilityWidgetProvider`, `OverallocatedEmployeesWidgetProvider`, `UpcomingCapacityRisksWidgetProvider` |
| Seeders | `ResourcePlanningSeeder` → permission / widget / quick-action seeders |
| Migrations | `2026_07_22_000030_create_resource_planning_tables.php` (+ metadata / permission sync migrations) |
| Web routes | `routes/web.php` under `resources.*` |
| API routes | `routes/api.php` under `/api/v1` |

## Service extension
Prefer extending existing services over duplicating rules:
- `ResourceCalendarService` — calendar CRUD, working-day/hour resolution, default seeding
- `ResourceAllocationService` — allocate/update/release, overlap percentage cap, capacity events, notifications
- `WorkloadService` — day rolls, leave factors, utilization status, snapshots, overallocation detection
- `CapacityPlanningService` — forecast load (allocations + open task hours), upcoming risks

Set `TenantContext` before tenant-scoped queries in controllers, jobs, and widget providers.

## Calendar resolution
`ResourceCalendarService::workingHoursForDay()` / `workingDaysForEmployee()`:

1. Resolve the employee’s `ResourceCalendar` effective on the date (`effective_from` ≤ date and `effective_to` null or ≥ date; latest `effective_from` wins).
2. If the weekday is not in the resolved working-days list → **0** hours.
3. Hours: calendar `working_hours_per_day` if present; else HRMS shift via `AttendanceService::resolveShiftForEmployee()`; else `config('resources.default_working_hours_per_day')`.
4. Working-days list without a calendar: org `settings.working_days` → `config('hrms.working_days')` → `config('resources.default_working_days')`.

Overlapping effective windows for the same employee are rejected on create/update.

## LeaveService reuse
Do **not** reimplement leave queries. Both `WorkloadService` and `CapacityPlanningService` inject `App\Services\Hrms\LeaveService` and call `getApprovedLeaveForDateRange($employee, $from, $to)`.

Workload maps applications to a per-day **leave factor** (`1` / `0.5` / `0`). Capacity planning separately counts leave **days** on working days for forecast metadata.

## Events and workflows
Subscribe listeners or workflow definitions to:

| Event class | Workflow key (`config/resources.php` / `config/workflows.php`) |
| --- | --- |
| `ResourceAllocated` | `resource.allocated` |
| `ResourceAllocationUpdated` | `resource.allocation_updated` |
| `ResourceReleased` | `resource.released` |
| `CapacityExceeded` | `resource.capacity_exceeded` |
| `OverallocationDetected` | `resource.overallocation_detected` |

Entity for workflow catalog: `resource_allocation`. Events use the platform `forModel()` factory pattern with actor / causation metadata where applicable. Capacity and overallocation events fire after create/update when utilization status for the allocation window is `overallocated`.

## RBAC slugs
Defined in `config/rbac.php` (and mirrored in dynamic RBAC templates):

```
resources.view
resources.allocate
resources.manage
resources.export
```

| Capability | Typical slug(s) |
| --- | --- |
| View planner / workload / capacity / calendars | `resources.view` |
| Allocation CRUD | `resources.allocate` or `resources.manage` |
| Calendar CRUD + workload snapshots | `resources.manage` |
| Export | `resources.export` |

Re-run `ResourcePermissionSeeder` (via `ResourcePlanningSeeder`) after adding slugs so organizations and role templates receive grants.

## Metadata entity `resource_allocation`
- Entity key: **`resource_allocation`** (`config/metadata.php`)
- Persist through `MetadataEntityFormService` on allocation create/update rather than writing JSON directly

## Dashboard widgets
Extend `AbstractWidgetProvider`, register in `config/dashboard.php`, then run `ResourceWidgetSeeder`. Existing keys: `team_workload`, `resource_availability`, `overallocated_employees`, `upcoming_capacity_risks`.

## Testing recommendations
- Org-scoped isolation (404 on foreign employee/allocation IDs)
- Overlap percentage rejection above `max_allocation_percentage`
- Leave factor reducing available hours and tipping status to `overallocated`
- Calendar resolution fallbacks (calendar → shift → config default)
- Forecast includes open task `estimated_hours` for assigned users
- Permission gates on policies (`view` vs `allocate` vs `manage`)

Suggested commands (see progress doc):

```bash
php artisan test tests/Unit/Resource*Test.php tests/Feature/Resource*.php tests/Feature/Workload*.php tests/Feature/Capacity*.php
```

## Related Documentation
See [overview](overview.md), [workload-calculations](workload-calculations.md), [capacity-planning](capacity-planning.md), [apis](apis.md), and [../projects/developer-guide.md](../projects/developer-guide.md).
