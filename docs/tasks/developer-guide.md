# Tasks Developer Guide

## Purpose
Guide for extending Task & Work Management with services, events, permissions, metadata, and widgets.

## Key paths
| Area | Path |
| --- | --- |
| Config | `config/tasks.php` |
| Models | `app/Models/Task*.php` |
| Services | `app/Services/Task*.php`, `ChecklistService`, `TimeTrackingService` |
| Events | `app/Events/Task*.php`, `DependencyCreated`, `DependencyRemoved`, `ChecklistCompleted`, `CommentAdded`, `TimeLogged` |
| Policies | `app/Policies/Task*.php` |
| Widgets | `app/Services/Dashboard/Widgets/*Task*WidgetProvider.php`, `TimeLoggedTodayWidgetProvider` |
| Seeders | `database/seeders/Task*Seeder.php` |
| Migrations | `database/migrations/2026_07_06_000028_create_tasks_table.php`, `2026_07_22_000020_create_task_work_management_tables.php` |
| Web routes | `routes/web.php` (task resource and nested panels) |
| API routes | `routes/api.php` under `/api/v1` |

## Service extension
Prefer extending existing services over duplicating rules:
- `TaskService` — create/update, assign, complete, archive, progress, numbering, metadata
- `TaskDependencyService` — typed edges and cycle prevention
- `ChecklistService` — checklist mutations and progress hooks
- `TimeTrackingService` — manual/timer logging and `actual_hours`
- `TaskDefaultsService` — idempotent catalog seeding

Set `TenantContext` before tenant-scoped queries in controllers, jobs, and widget providers.

## Events and workflows
Subscribe listeners or workflow definitions to:

| Event class | Workflow key (config) |
| --- | --- |
| `TaskCreated` | `task.created` |
| `TaskUpdated` | `task.updated` |
| `TaskAssigned` | `task.assigned` |
| `TaskReassigned` | `task.reassigned` |
| `TaskStarted` | `task.started` |
| `TaskCompleted` | `task.completed` |
| `TaskArchived` | `task.archived` |
| `TaskRestored` | `task.restored` |
| `DependencyCreated` | `task.dependency_created` |
| `DependencyRemoved` | `task.dependency_removed` |
| `ChecklistCompleted` | `task.checklist_completed` |
| `CommentAdded` | `task.comment_added` |
| `TimeLogged` | `task.time_logged` |

Events use the platform `forModel()` factory pattern and include actor / causation metadata where applicable.

## Metadata entity `task`
- Entity key: **`task`** (`config/metadata.php`)
- Storage: `tasks.metadata` JSON with `custom_fields` attribute alias
- Persist through `MetadataEntityFormService` rather than writing JSON directly
- Controllers load fields for contexts `create`, `edit`, and `detail`

## RBAC slugs
Defined in `config/rbac.php` (and mirrored in dynamic RBAC templates):

```
tasks.view, tasks.create, tasks.edit, tasks.update, tasks.delete,
tasks.archive, tasks.restore, tasks.assign, tasks.comment, tasks.attachments,
tasks.time-log, tasks.manage-status, tasks.manage-priority,
tasks.manage-dependencies, tasks.manage-checklists,
tasks.export, tasks.import, tasks.manage
```

Re-run `TaskPermissionSeeder` after adding slugs so organizations and role templates receive grants.

## Extending catalogs
- Statuses: `TaskStatusService` + `config/tasks.default_statuses`
- Priorities: `TaskPriorityService` + `config/tasks.default_priorities`
- Dependency types / time sources: extend maps in `config/tasks.php` and keep service validation in sync

## Dashboard widgets
Extend `AbstractWidgetProvider`, register in `config/dashboard.php`, then run `TaskWidgetSeeder`.

## Testing recommendations
- Org-scoped isolation (404 on foreign IDs)
- Archive read-only and closed-task time/checklist guards
- Dependency cycle rejection
- Progress recalculation from checklists and children
- Timer single-running-timer constraint and `actual_hours` rollup
- Permission gates on policies

Suggested commands (see progress doc):

```bash
php artisan test tests/Unit/TaskServiceTest.php tests/Feature/Task*.php
```

## Related Documentation
See [architecture](architecture.md), [apis](apis.md), [lifecycle](lifecycle.md), and [../projects/developer-guide.md](../projects/developer-guide.md).
