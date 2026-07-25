# Progress User Guide

## Purpose
How project managers and stakeholders track delivery progress, health, and reports in NovaCRM.

## Posting a progress update
1. Open a project → **Progress** (`projects/{project}/progress`) or Progress Center dashboard.
2. Enter **Progress %** (0–100), **Summary** (required), optional blockers and next steps.
3. Optionally link a milestone.
4. Submit — the update appears in history; owner and manager receive a notification.

Each submission adds a new history row. Editing an existing update changes that row only (does not create a duplicate entry).

## Progress Center
**Progress Center** (`projects/{project}/progress/dashboard`) combines:
- Current health status and completion %
- Task statistics and velocity
- Milestone progress (planned vs actual)
- Timeline summary and overdue/delayed highlights

Requires `projects.progress.view`.

## Project health
**Health** page shows the latest calculated status:

| Status | Typical meaning |
| --- | --- |
| On Track | No threshold breaches |
| At Risk | Some overdue work or schedule slip |
| Delayed | Significant overdue tasks/milestones or past plan |
| Completed | Project finished or 100% weighted completion |
| Archived | Project archived |

Use **Recalculate** to refresh metrics after task or milestone changes. Requires `projects.health.view`.

## Reports
From **Reports** on a project:
1. Choose report type (Summary, Task Progress, Milestone Status, etc.).
2. Choose format: PDF, Excel, or CSV.
3. Generate — download when ready; you receive a notification.

Requires `projects.reports.generate` to create; `projects.reports.view` to list.

## Gantt chart
**Gantt** view (`projects/{project}/gantt`) shows project, milestone, and task bars with dependencies. Requires `projects.gantt.view`.

## Executive portfolio
**Projects → Executive** (`projects/executive`) shows portfolio health breakdown and projects at risk. Requires `projects.view`.

## Permissions summary
| Role template | Typical access |
| --- | --- |
| Organization owner / Manager | Full progress, health, reports, timeline, statistics |
| Sales executive / Support | View progress and health; limited create |
| HR | No project progress access by default |

Contact your administrator to adjust role permissions.

## Related Documentation
See [progress-tracking](progress-tracking.md), [project-health](project-health.md), [reporting](reporting.md), and [executive-dashboard](executive-dashboard.md).
