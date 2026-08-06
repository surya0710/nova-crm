# Recurring Tasks

## Overview
Tasks can carry a recurrence schedule (`task_recurrences`). An hourly console command generates due occurrences via `TaskGenerationService`, copying the source task forward according to type, interval, end rules, and optional holiday skipping.

## Database Tables
| Table | Model | Purpose |
| --- | --- | --- |
| `task_recurrences` | `TaskRecurrence` | Schedule attached to a source task |

Key fields: `recurrence_type` (`daily`, `weekly`, `monthly`, `quarterly`, `yearly`, `custom`), `interval`, `days_of_week`, `end_type` (`never` / `date` / `occurrences`), `end_date`, `occurrences`, `generated_count`, `skip_holidays`, `copy_attachments`, `is_active`, `next_run_at`, `last_generated_at`, `settings`.

## Services
`TaskRecurrenceService`:
- `create` / `update` / `delete` — manage schedules on a task
- `calculateNextRunAt` / `calculateNextRunAtFromValues` — next fire time
- Holiday-aware advance when `skip_holidays` is set

`TaskGenerationService`:
- `generateDue()` — used by `projects:generate-recurring-tasks` (scheduled hourly in `routes/console.php`)
- Dispatches `TaskRecurrenceGenerated` when occurrences are created

## RBAC Permissions
| Slug | Capability |
| --- | --- |
| `projects.recurrence.view` | View recurrence settings |
| `projects.recurrence.manage` | Create, update, delete recurrence rules |

## Workflow Events
| Trigger | Event |
| --- | --- |
| `task.recurrence.created` | `TaskRecurrenceCreated` |
| `task.recurrence.generated` | `TaskRecurrenceGenerated` |
| `task.recurrence.deleted` | `TaskRecurrenceDeleted` |

(`TaskRecurrenceUpdated` is also emitted on update; wire into workflows if a dedicated trigger is added.)

## API / Routes Summary
| Surface | Routes |
| --- | --- |
| Web | `POST tasks/{task}/recurrence`; `PATCH/DELETE tasks/{task}/recurrence/{recurrence}` |
| API | `POST/PUT/PATCH/DELETE` same paths under `/api` |
| Console | `php artisan projects:generate-recurring-tasks` (hourly) |

## UI
Recurrence controls live on the task detail surface (store/update/destroy routes). Generated child tasks appear in the normal task list for the project.

## Acceptance Notes
- Archived tasks reject new recurrence rules.
- Generation increments `generated_count` and advances `next_run_at`; inactive schedules are skipped.
- End conditions (`date` / `occurrences`) stop further generation when reached.
- Schedules are organization-scoped and cascade when the source task is deleted.
