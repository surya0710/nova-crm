# Resource Planning User Guide

## Purpose
Guide project managers and team leads through allocating capacity, reading workload, and spotting capacity risks in NovaCRM.

## Who should use this feature
- Delivery leads planning who works on which project or task
- Managers reviewing team utilization
- Admins configuring per-employee working calendars

## Prerequisites
- Organization membership with at least `resources.view`
- Employees provisioned in HRMS (active / probation / notice period appear in team views)
- Optional: projects and open tasks when allocating delivery work
- Permissions and widgets seeded (`ResourcePlanningSeeder`)

## Views
| View | Route name | Use |
| --- | --- | --- |
| Planner | `resources.planner` | Allocations and team workload in a date window |
| Capacity | `resources.capacity` | Team capacity / utilization summary |
| Employee workload | `resources.employees.workload` | Day-by-day breakdown for one employee |
| Timeline | `resources.timeline` | Allocation timeline |
| Forecast | `resources.forecast` | Capacity forecast and risk-oriented view |
| Calendars | `resources.calendars.*` | Manage working hours and days |
| Allocations | `resources.allocations.*` | CRUD resource allocations |

## Step-by-step instructions
1. Open **Resources → Planner** (or Capacity) and set the date range you care about.
2. Optionally create a **calendar** for an employee when their hours or working days differ from org defaults (effective_from / effective_to windows must not overlap).
3. Create an **allocation**: choose employee, type, percentage, start/end dates. For `project` / `task` types, select the project (and task when required).
4. Keep overlapping allocations so daily percentage totals stay within the configured max (default 100%). Concurrent work is fine when percentages sum correctly.
5. Review **employee workload** to see available vs allocated hours after leave.
6. Check **forecast** / capacity risks for upcoming overallocation (planned load plus open task estimates).
7. Update or delete (release) allocations when plans change; assigned employees receive notifications when allocated or when capacity is exceeded.

## Dashboard widgets
| Widget | Shows |
| --- | --- |
| Team Workload | Team utilization overview |
| Resource Availability | Employees with spare capacity |
| Overallocated Employees | People over planned capacity |
| Upcoming Capacity Risks | Forecast risks in the coming days |

Quick actions include creating an allocation and opening team capacity (when seeded).

## Expected result
Allocations appear on the planner and in workload/capacity views. Utilization statuses are **underutilized**, **optimal**, or **overallocated** based on configured thresholds. Leave and non-working days reduce available hours; open task estimates increase forecast load.

## Permissions summary
- `resources.view` — planner, capacity, workload, forecast, calendars list, widgets
- `resources.allocate` — create/update/delete allocations
- `resources.manage` — calendars CRUD, workload snapshots, full admin
- `resources.export` — export resource/workload reports (when exposed)

## Related Documentation
See [overview](overview.md), [capacity-planning](capacity-planning.md), [workload-calculations](workload-calculations.md), and [apis](apis.md).
