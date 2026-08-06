# Capacity Planning Guide

## Purpose
Describe how Konnect Nex forecasts employee load, surfaces overallocation risks, and combines planned allocations with open task estimates.

## Service
`App\Services\CapacityPlanningService` builds forecasts for a single `Employee` or an entire `Organization`.

## Forecast inputs
For each active employee (statuses from `config('hrms.leave_applicable_employee_statuses')`, default `active`, `probation`, `notice_period`):

| Input | Source |
| --- | --- |
| Capacity / available / allocated hours | `WorkloadService::calculateForEmployee()` (calendars + leave) |
| Open task estimated hours | Non-archived open tasks assigned to the employee’s `user_id`, with `estimated_hours`, overlapping the date range |
| Leave days | Approved leave via `LeaveService::getApprovedLeaveForDateRange()`, counted only on working days (half-day = 0.5) |
| Allocations list | Overlapping `resource_allocations` rows in the window |

## Forecast load
```
forecast_load_hours = allocated_hours + open_task_estimated_hours
utilization         = (forecast_load_hours / available_hours) × 100
status              = WorkloadService::statusForUtilization(utilization)
```

When `available_hours` is 0, utilization is `100` if there is any forecast load, otherwise `0`.

Organization forecasts return per-employee rows plus a `summary` (`employee_count`, totals for available/allocated/open-task/forecast hours, `overallocated_count`).

## Upcoming risks
`upcomingRisks(Organization, ?days)` looks ahead `days` (default `config('resources.capacity_risk_days')` = 14) and returns employees whose forecast status is `overallocated`, sorted by utilization descending.

Web: `resources.forecast`, `resources.capacity`.  
API: `GET /api/v1/capacity/forecast`, `GET /api/v1/capacity/risks`.

## Ending-soon notifications
`ResourceAllocationService::notifyEndingSoon()` notifies employees whose allocations end within the risk window. Intended for scheduled sweeps; safe to call repeatedly.

## Related Documentation
See [workload-calculations](workload-calculations.md), [overview](overview.md), and [apis](apis.md).
