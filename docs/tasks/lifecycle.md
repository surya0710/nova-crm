# Task Lifecycle

## Purpose
Describe how tasks are created, assigned, started, completed, archived, and restored, including closed and read-only rules.

## Status vs archive
- **Status** — operational state from `task_statuses` (`status_id`), kept in sync with legacy string `status`
- **Archive** — `is_archived` flag; archived tasks are read-only for core updates

Closed statuses (`is_closed = true`) mark terminal work states. Archive is independent of status.

## Default statuses
Seeded from `config/tasks.php` via `TaskDefaultsService`:

| Sort | Slug | Closed | Default |
| --- | --- | --- | --- |
| 10 | `to-do` | No | Yes |
| 20 | `in-progress` | No | |
| 30 | `review` | No | |
| 40 | `blocked` | No | |
| 50 | `completed` | Yes | |
| 60 | `cancelled` | Yes | |

Legacy CRM strings (`pending`, `in_progress`, `completed`, `cancelled`) map to catalog slugs through `legacy_status_slug_map` / `status_slug_legacy_map`.

## Create
### Work-management path
`TaskService::createWorkManagement()` / `createForProject()`:
- Requires `project_id` in the same organization
- Rejects archived projects
- Assigns `task_number` (`TASK-0001` style) and `slug`
- Defaults `status_id` / `priority_id` from organization defaults
- Emits `TaskCreated` (and `TaskAssigned` when assignee is set)

### Legacy / polymorphic path
`TaskService::create()` / `createFor($subject)` may attach to lead, customer, opportunity, or project via `config('tasks.taskable')` without requiring work-management numbering.

## Assign
`TaskService::assign()` updates `assigned_to` / `assigned_by`, notifies the assignee, and emits `TaskAssigned` or `TaskReassigned`. Assignee must be an organization member. Blocked when the task is archived.

Web: `PATCH tasks/{task}/assign` (`tasks.assign`).  
API: `POST /api/v1/tasks/{task}/assign`.

## Start
Moving catalog status to slug `in-progress` during `TaskService::update()` emits `TaskStarted`. There is no separate “start” endpoint.

## Complete
`TaskService::complete()` sets status to the `completed` catalog entry (legacy `status = completed`), `completed_at`, and `completion_percentage = 100`. Emits `TaskCompleted` when the status slug becomes `completed`.

Web: `PATCH tasks/{task}/complete` (`tasks.complete`).  
API: `POST /api/v1/tasks/{task}/complete`.

Updating to any closed status also sets `completed_at` when previously unset; reopening clears `completed_at`.

## Archive and restore
| Action | Behavior |
| --- | --- |
| Archive | Sets `is_archived = true`; emits `TaskArchived` |
| Restore | Clears `is_archived`; emits `TaskRestored` |

Web: `POST tasks/{task}/archive`, `POST tasks/{task}/restore`.  
API: same path verbs under `/api/v1`.

## Closed and read-only rules
| State | Core field updates (`TaskService`) | Checklists | Time logging | Comments / attachments |
| --- | --- | --- | --- | --- |
| Open | Allowed | Allowed | Allowed | Allowed (not archived) |
| Closed (`is_closed`) | Allowed via status/field update* | Blocked | Blocked | Allowed if not archived |
| Archived | Blocked (`assertWritable`) | Blocked | Blocked | Blocked |

\* `Task::isReadOnly()` is true only for archived tasks. Closed tasks still block checklist mutations and time logging via `ChecklistService` / `TimeTrackingService`.

## Progress
`TaskService::calculateProgress()` averages checklist completion and child-task progress when present, updates `completion_percentage`, and bubbles to `parent_task_id` when set.

## Related Documentation
See [architecture](architecture.md), [checklists](checklists.md), [time-tracking](time-tracking.md), and [administrator-guide](administrator-guide.md).
