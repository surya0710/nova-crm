# P12 Phase 12.3 — Resource Planning & Workload Progress

## Phase
Phase 12.3 — Resource Planning & Workload Management

## Outcome
Organization-scoped Resource Planning is implemented end-to-end on top of Projects, Tasks, and HRMS: resource calendars, allocations with overlap percentage caps, workload calculation (leave-aware available hours and utilization statuses), capacity forecasting and risks, domain events/workflow triggers, RBAC, metadata entity `resource_allocation`, dashboard widgets/quick actions, REST APIs, Blade planner UI, documentation, and tests.

## Delivered

| Area | Status |
| --- | --- |
| Tables (`resource_calendars`, `resource_allocations`, `workload_snapshots`) + allocation metadata | Done |
| Models, services (`ResourceCalendarService`, `ResourceAllocationService`, `WorkloadService`, `CapacityPlanningService`) | Done |
| Leave-aware workload + calendar resolution (calendar → shift → config defaults) | Done |
| Domain events + workflow triggers (`resource.*`) | Done |
| Dynamic RBAC permissions (`resources.*`) + role template grants | Done |
| Metadata entity `resource_allocation` | Done |
| Dashboard widgets + quick actions | Done |
| Web + API controllers/routes | Done |
| Blade UI (planner, capacity, workload, timeline, forecast, calendars, allocations) | Done |
| Seeders (`ResourcePlanningSeeder`) | Done |
| Documentation (`docs/resources/*`) | Done |
| Feature/unit tests (`tests/Unit/Resource*Test.php`, `tests/Feature/Resource*.php`, `Capacity*.php`) — 29 passing | Done |

## Run

```bash
php artisan migrate
php artisan db:seed --class=ResourcePlanningSeeder
php artisan test tests/Unit/Resource*Test.php tests/Feature/Resource*.php tests/Feature/Workload*.php tests/Feature/Capacity*.php
```

## Notes
- Concurrent allocations are allowed; daily sum of `allocation_percentage` cannot exceed `max_allocation_percentage` (default 100).
- Utilization statuses (`underutilized` / `optimal` / `overallocated`) use `underutilization_threshold` (50) and `overallocation_threshold` (100) from `config/resources.php`.
- Available hours apply approved leave factors via `LeaveService::getApprovedLeaveForDateRange()`; do not duplicate leave logic in new callers.
- Capacity forecast load = allocated hours + open task `estimated_hours` for the employee’s linked user.
- Workload snapshots require `resources.manage`; calendar CRUD requires `resources.manage`; allocation CRUD requires `resources.allocate` or `resources.manage`.
