# Workload Calculations

## Purpose
Document the formulas used by `WorkloadService` to compute capacity, available hours, utilization, and status labels.

## Day-level building blocks
For each calendar day in `[from, to]`:

| Symbol | Definition |
| --- | --- |
| `capacity_hours` | Working hours for that weekday from calendar resolution (`ResourceCalendarService::workingHoursForDay`). Non-working days are `0`. |
| `leave_factor` | From approved leave: `1.0` (none), `0.5` (half-day), `0.0` (full day). Full leave wins over half-day when both apply. |
| `available_hours` | `capacity_hours × leave_factor` |
| `allocation_percentage` | Sum of overlapping allocation percentages for the employee on that day |
| `allocated_hours` | `capacity_hours × (allocation_percentage / 100)` |
| Day `utilization` | `(allocated_hours / available_hours) × 100` when available > 0; else `100` if allocated > 0, otherwise `0` |

Leave is loaded once via `LeaveService::getApprovedLeaveForDateRange()` and applied per day.

## Range totals (conceptual)
Across the date range:

```
capacity  = Σ capacity_hours
available = Σ available_hours   ≈ (working days × hours) − leave hours
allocated = Σ allocated_hours
utilization = (allocated / available) × 100   // or 100/0 when available is 0
```

“Working days × hours − leave” is the intuitive form: available hours are full working-day hours reduced by leave factors, not by counting leave days separately in the utilization denominator.

## Utilization statuses
`WorkloadService::statusForUtilization()` uses `config/resources.php`:

| Status | Condition (defaults) |
| --- | --- |
| `underutilized` | `utilization < underutilization_threshold` (default **50**) |
| `overallocated` | `utilization > overallocation_threshold` (default **100**) |
| `optimal` | otherwise (inclusive of the under threshold and up through the over threshold) |

Labels for UI/API catalogs: `config('resources.utilization_statuses')`.

## Overlap hard cap vs utilization status
At create/update time, `ResourceAllocationService` rejects any day where overlapping allocation percentages would exceed `max_allocation_percentage` (default **100**). Concurrent allocations are allowed when their percentages sum to at most that max on every overlapping day.

Utilization can still be `overallocated` when leave reduces available hours while planned percentages remain high, or when capacity forecast adds open task estimate hours (see [capacity-planning](capacity-planning.md)).

## Snapshots
`snapshotEmployee` / `snapshotTeam` persist one day of totals into `workload_snapshots` (`allocated_hours`, `available_hours`, `utilization_percentage`, `overall_status`). Creating snapshots via API requires `resources.manage`.

## Active employees
Team and detect-overallocation paths only include employees in leave-applicable statuses (`config('hrms.leave_applicable_employee_statuses')`).

## Related Documentation
See [capacity-planning](capacity-planning.md), [developer-guide](developer-guide.md), and [apis](apis.md).
