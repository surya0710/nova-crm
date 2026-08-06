# Calendar Sync

## Overview
Projects, milestones, and tasks with due dates sync into `project_calendar_links` for an internal project calendar. Google/Outlook adapters are stubbed (`syncToGoogle` / `syncToOutlook` throw “not implemented”) so providers can be added later without schema changes.

## Database Tables
| Table | Model | Purpose |
| --- | --- | --- |
| `project_calendar_links` | `ProjectCalendarLink` | Synced calendar events (provider, event type, title, times, sync status) |

Event types include `project_deadline`, `milestone_due`, `task_due`. Provider default: `internal`.

## Services
`CalendarSyncService`:
- `syncProject($project)` — upsert deadline, milestone, and task due links
- `syncTask($task)` — upsert or remove task due link when due dates change
- `listCalendarEvents($organization, $filters)` — date-range / project filters
- `detectConflicts($user, $startsAt, $endsAt)` — overlapping links for a user
- `syncToGoogle` / `syncToOutlook` — reserved external providers

## RBAC Permissions
| Slug | Capability |
| --- | --- |
| `projects.calendar.view` | View project calendar |
| `projects.calendar.manage` | Trigger sync and manage links |

## Workflow Events
No dedicated calendar workflow triggers in Phase 12.5. Sync is invoked from controllers and can be hooked later; collaboration/project events remain the primary automation surface.

## API / Routes Summary
| Surface | Routes |
| --- | --- |
| Web | `GET projects/calendar`; `POST projects/{project}/calendar/sync` |
| API | `GET api/projects/calendar`; `POST api/projects/{project}/calendar/sync` |

## UI
- Calendar index: `resources/views/projects/calendar/index.blade.php`
- Per-project sync action from project/collaboration surfaces
- Dashboard widget gated by `projects.calendar.view`

## Acceptance Notes
- Sync is upsert-based (stable identity per project/task/milestone + event type).
- Clearing a task due date removes the corresponding internal `task_due` link.
- External providers must not be assumed live until implemented.
- Links are organization-scoped and cascade with related entities.
