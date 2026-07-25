# Tasks User Guide

## Purpose
Guide assignees, project members, and managers through everyday task work in NovaCRM.

## Who should use this feature
- People assigned follow-ups and delivery work
- Project managers tracking project tasks
- Team leads reviewing board, list, and timeline views

## Prerequisites
- Organization membership with at least `tasks.view`
- Task catalogs seeded (`TaskFoundationSeeder`) for statuses and priorities
- Optional: a Project record when creating work-management tasks

## Views
| View | Route name | Use |
| --- | --- | --- |
| Index / list | `tasks.index`, `tasks.list` | Browse and filter tasks |
| Board | `tasks.board` | Status columns |
| Timeline | `tasks.timeline` | Date-oriented overview |
| Project tasks | `projects.tasks.index` | Tasks for one project |
| Detail | `tasks.show` | Full task with related panels |

## Step-by-step instructions
1. Open **Tasks** (list, board, or timeline) or a project’s task list.
2. Create a task with title, status, priority, due date, and assignee. Prefer linking a **project** for work-management numbering (`TASK-####`).
3. Optionally attach the task to a CRM lead, customer, opportunity, or project (polymorphic taskable).
4. Assign or reassign via the assign action when ownership changes.
5. Add checklist items and mark them complete as work progresses (progress percentage updates automatically).
6. Log time manually or start/stop a timer from the time logs panel.
7. Add comments (with `@username` mentions when supported) and upload attachments as needed.
8. Link predecessor tasks under Dependencies when work must wait on another task.
9. Mark the task complete when finished, or archive it to remove it from active work while keeping history.
10. Restore archived tasks if they need further edits.

## Dashboard widgets
| Widget | Shows |
| --- | --- |
| My Tasks | Open tasks assigned to you |
| Tasks Due Today | Open tasks due today |
| Overdue Tasks | Open tasks past due |
| Recently Updated Tasks | Latest task activity |
| Time Logged Today | Minutes logged today |
| Team Task Summary | Team open-work summary (manage permission) |

## Expected result
Tasks appear on boards and lists with the correct status, assignee, progress, and time. Closed tasks no longer accept new time logs or checklist edits. Archived tasks are read-only.

## Permissions summary
- `tasks.view` — browse tasks and widgets
- `tasks.create` / `tasks.edit` — create and update
- `tasks.assign` — change assignee
- `tasks.comment` / `tasks.attachments` / `tasks.time-log` — collaboration and time
- `tasks.manage-checklists` / `tasks.manage-dependencies` — structure work
- `tasks.archive` / `tasks.restore` / `tasks.delete` — lifecycle control

## Related Documentation
See [lifecycle](lifecycle.md), [checklists](checklists.md), [time-tracking](time-tracking.md), [dependencies](dependencies.md), and [administrator-guide](administrator-guide.md).
