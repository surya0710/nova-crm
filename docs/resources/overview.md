# Resource Planning Guide

## Purpose
Introduces Resource Planning & Workload Management for allocating employee capacity across projects, tasks, and non-delivery work, then measuring utilization against calendars and approved leave.

## Core Areas
- Per-employee **resource calendars** (working days, hours per day, effective windows)
- **Resource allocations** with typed work (`project`, `task`, `support`, `internal`, `leave`, `training`)
- **Workload** calculation (available vs allocated hours, utilization status)
- **Capacity forecast** and upcoming overallocation risks
- Dashboard widgets and planner UI for managers
- Workflow triggers on allocate / update / release / capacity events

## Platform Ownership
Resources owns calendars, allocations, workload snapshots, and capacity forecasting. Projects and Tasks provide allocatable work. HRMS owns employees, shifts, and approved leave via `LeaveService`. RBAC owns `resources.*` permissions. Metadata Platform may attach custom fields to entity key `resource_allocation`.

## Phase Scope (12.3)
Phase 12.3 delivers schema (`resource_calendars`, `resource_allocations`, `workload_snapshots`), services, policies, Sanctum REST APIs, Blade planner UI, RBAC seeders, dashboard widgets/quick actions, workflow catalog entries, documentation, and tests.

## Configuration
Defaults and catalogs live in `config/resources.php`:
- Allocation types and utilization status labels
- `default_working_hours_per_day` (8) and `default_working_days` (Mon–Fri)
- `max_allocation_percentage` (100) — concurrent allocations allowed if daily percentage sum ≤ max
- `underutilization_threshold` (50) and `overallocation_threshold` (100)
- `capacity_risk_days` (14) for forecast risk windows
- Workflow trigger keys under `workflow_triggers`

## Related Documentation
See [capacity-planning](capacity-planning.md), [workload-calculations](workload-calculations.md), [apis](apis.md), [user-guide](user-guide.md), and [developer-guide](developer-guide.md).
