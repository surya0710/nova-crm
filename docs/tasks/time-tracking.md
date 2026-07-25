# Time Tracking

## Purpose
Describe how time is logged against tasks, how actual hours are calculated, and closed/archived restrictions.

## Sources
Configured in `config/tasks.php` as `time_log_sources`:

| Source | Meaning | Implemented by |
| --- | --- | --- |
| `manual` | Explicit start/end or duration entry | `TimeTrackingService::logManual()` |
| `timer` | Running timer with null `end_time` until stopped | `startTimer()` / `stopTimer()` |
| `import` | Reserved source value for imported rows | Config/schema only in Phase 12.2 (no import API writer yet) |

Rows live in `task_time_logs` (`start_time`, `end_time`, `duration_minutes`, `description`, `source`, `user_id`).

## Manual logging
`logManual()` requires a trackable task, parses `start_time` / optional `end_time`, computes or accepts `duration_minutes`, creates a `manual` log, refreshes `actual_hours`, and emits `TimeLogged`.

## Timer
| Action | Behavior |
| --- | --- |
| Start | Creates a `timer` row with `end_time = null`; rejects if the user already has a running timer on that task |
| Stop | Sets `end_time`, computes `duration_minutes`, refreshes `actual_hours`, emits `TimeLogged` |

One running timer per user per task.

## Actual hours
`refreshActualHours()` sums `duration_minutes` for logs with non-null `end_time` and stores `tasks.actual_hours` as hours rounded to two decimals. Running timers do not count until stopped.

`TaskStatisticsService` can report estimated vs actual for a task or organization.

## Closed and archived rules
`assertTrackable()` blocks logging when:
- the task is **archived** — “Cannot log time on an archived task.”
- the task is **closed** (`TaskStatus.is_closed` or legacy completed/cancelled) — “Cannot log time on a closed task.”

## Routes
| Surface | List | Manual create | Start | Stop | Delete |
| --- | --- | --- | --- | --- | --- |
| Web | `tasks.time-logs.index` | `tasks.time-logs.store` | `tasks.time-logs.start` | `tasks.time-logs.stop` | `tasks.time-logs.destroy` |
| API | `GET /api/v1/tasks/{task}/time-logs` | `POST …/time-logs` | `POST …/time-logs/start` | `POST …/time-logs/stop` | `DELETE …/time-logs/{time_log}` |

Permission: `tasks.time-log` (delete uses time-log policy).

## Related Documentation
See [lifecycle](lifecycle.md), [architecture](architecture.md), and [apis](apis.md).
