# Project Health

## Purpose
Automated health scoring for projects from task/milestone/manual completion, schedule variance, overdue work, and lifecycle state. Each calculation persists an immutable snapshot.

## Health Statuses
Configured in `config/projects.php` → `health_statuses`:

| Status | Meaning |
| --- | --- |
| `on_track` | Within thresholds |
| `at_risk` | Early warning (overdue tasks/milestones or schedule slip) |
| `delayed` | Material slip against thresholds |
| `completed` | Closed status or weighted completion ≥ 100% |
| `archived` | Project archived |

## Completion Weights
`completion_weights` (default):

| Component | Weight | Source |
| --- | --- | --- |
| Tasks | 0.5 | Average `completion_percentage` of project tasks |
| Milestones | 0.3 | % milestones with status `completed` |
| Manual | 0.2 | Latest `ProgressUpdate` or `projects.completion_percentage` |

`ProjectHealthService::calculateCompletionPercentage()` returns weighted integer 0–100.

## Thresholds
`health_thresholds` drive `determineHealthStatus()`:

| Key | Default | Effect |
| --- | --- | --- |
| `overdue_tasks_at_risk` | 1 | ≥ → `at_risk` |
| `overdue_tasks_delayed` | 3 | ≥ → `delayed` |
| `missed_milestones_at_risk` | 1 | ≥ → `at_risk` |
| `missed_milestones_delayed` | 2 | ≥ → `delayed` |
| `schedule_variance_at_risk_days` | 3 | ≥ days past plan → `at_risk` |
| `schedule_variance_delayed_days` | 7 | ≥ → `delayed` |

Evaluation order: archived → completed → delayed → at_risk → on_track.

## Snapshots
Table `project_health_snapshots` / model `ProjectHealthSnapshot`:

- `health_status`, `completion_percentage`, `schedule_variance`, `budget_variance`
- `estimated_completion_date` (velocity-based when plan dates exist)
- `calculated_at`, `metadata.metrics` (task/milestone counts, overdue IDs)

Latest snapshot: `healthSnapshots()` ordered by `calculated_at` desc. Recalculate via `ProjectHealthService::calculate()` or `?recalculate=1` on health endpoints.

## Service API
| Method | Description |
| --- | --- |
| `calculate($project, $actor?)` | Compute metrics, persist snapshot, dispatch events on status change |
| `latest($project)` | Most recent snapshot |
| `portfolioSummary($organization)` | Count by status (latest snapshot per project) |
| `detectOverdueTasks($project)` | Open tasks past due |
| `detectDelayedMilestones($project)` | Non-completed milestones past `due_date` |
| `predictCompletionDate($project)` | Estimated finish from elapsed progress |

## Events
| Event | Trigger |
| --- | --- |
| `ProjectHealthChanged` | Status changed (`project.health.changed`) |
| `ProjectCompleted` | First transition to `completed` |
| `ProjectDelayed` | First transition to `delayed` |

Stakeholder notifications (owner/manager) fire on health change and delay.

## UI & Widgets
- `GET projects/{project}/health` — health detail + snapshot history
- Dashboard widgets: `project_health`, `projects_at_risk` (require `projects.health.view`)

## Related Documentation
See [progress-tracking](progress-tracking.md), [executive-dashboard](executive-dashboard.md), and [progress-architecture](progress-architecture.md).
