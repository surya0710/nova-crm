# P12 Phase 12.5 — Collaboration Progress

## Phase
Phase 12.5 — Project Collaboration (labels, watchers, mentions, templates, recurring tasks, calendar, collaboration center, automation)

## Outcome
Organization-scoped collaboration features on top of Projects and Tasks: colored labels, project/task watchers with notification preferences, `@mention` history, project templates with clone-from-template, recurring task schedules, internal calendar sync links, Collaboration Center feed/pins, automation helpers for workflows, RBAC, dashboard widgets, REST APIs, Blade UI, global search integration, documentation, and tests.

## Delivered

| Area | Status |
| --- | --- |
| Tables (`project_labels`, `task_labels`, `project_watchers`, `task_watchers`, `task_recurrences`, `project_templates` + template children, `project_mentions`, `notification_preferences`, `project_calendar_links`, `project_collaboration_pins`) | Done |
| Models + services (labels, watchers, mentions, templates/clone, recurrence/generation, calendar sync, collaboration, automation, notification prefs) | Done |
| Domain events + workflow triggers (`project.label.*`, `task.label.*`, `*.watcher.*`, `comment.mentioned`, `task.recurrence.*`, `project.template.*`, `project.created_from_template`, `project.collaboration.updated`, `notification.preference.updated`) | Done |
| RBAC (`projects.labels.*`, `projects.watchers.*`, `projects.mentions.view`, `projects.templates.*`, `projects.recurrence.*`, `projects.collaboration.*`, `projects.automation.*`, `projects.calendar.*`, `projects.notifications.manage`) | Done |
| Dashboard widgets + quick actions | Done |
| Web + API controllers/routes | Done |
| Blade UI (labels, templates, mentions, watching, collaboration, calendar, automation) | Done |
| Seeders (`ProjectCollaborationSeeder` and related) | Done |
| Global search (templates, labels, mentions, discussion comments) | Done |
| Documentation (`docs/projects/*` collaboration guides + this phase file) | Done |

## Feature documentation

| Topic | Doc |
| --- | --- |
| Labels | [project-labels.md](projects/project-labels.md) |
| Watchers | [watchers.md](projects/watchers.md) |
| Mentions | [mentions.md](projects/mentions.md) |
| Recurring tasks | [recurring-tasks.md](projects/recurring-tasks.md) |
| Project templates | [project-templates.md](projects/project-templates.md) |
| Calendar sync | [calendar-sync.md](projects/calendar-sync.md) |
| Collaboration center | [collaboration-center.md](projects/collaboration-center.md) |
| Project automation | [project-automation.md](projects/project-automation.md) |

Also see [projects/overview.md](projects/overview.md) and [projects/architecture.md](projects/architecture.md) for Phase 12.5 pointers.

## Run

```bash
php artisan migrate
php artisan db:seed --class=ProjectCollaborationSeeder
php artisan projects:generate-recurring-tasks
php artisan test --filter=ProjectCollaboration
php artisan test --filter=ProjectLabel
php artisan test --filter=Watcher
php artisan test --filter=Mention
php artisan test --filter=ProjectTemplate
php artisan test --filter=TaskRecurrence
php artisan test --filter=CalendarSync
php artisan test --filter=Collaboration
```

## Notes
- Labels attach to tasks via `task_labels`; catalog is org-unique by name.
- Mentions parse `@handle` from comment bodies; self-mentions are skipped.
- Templates may be system-wide (`organization_id` null); clone materializes milestones/tasks/checklists/labels.
- Recurring generation runs hourly via `projects:generate-recurring-tasks`.
- Calendar sync defaults to provider `internal`; Google/Outlook stubs are reserved.
- Collaboration Center aggregates comments, progress, mentions, activity, watchers, and pins.
- Automation helpers belong in `ProjectAutomationService`, invoked from workflow actions.
