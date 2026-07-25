# Tasks Administrator Guide

## Purpose
Configure task statuses, priorities, permissions, and dashboard provisioning for the organization.

## Required configuration
- Organizations provisioned with RBAC roles
- `config/tasks.php` defaults available
- Tasks module permissions present in `config/rbac.php`
- Optional: Projects module for work-management tasks that require `project_id`

## Seeding defaults
Run the foundation seeder after migrations:

```bash
php artisan migrate
php artisan db:seed --class=TaskFoundationSeeder
```

`TaskFoundationSeeder` calls:
- `TaskStatusSeeder` — default statuses per organization
- `TaskPrioritySeeder` — default priorities per organization
- `TaskPermissionSeeder` — syncs `tasks.*` permissions to roles
- `TaskWidgetSeeder` — registers system dashboard widgets
- `TaskQuickActionSeeder` — registers system quick actions

Catalog seeding is idempotent (`updateOrCreate` / defaults service).

## Status administration
Web resource: `task-statuses` (`TaskStatusController`).

| Concern | Rule |
| --- | --- |
| Default | One default status for new tasks |
| Closed | `is_closed` marks terminal states (Completed, Cancelled by default) |
| Delete | Cannot delete statuses in use, the default status, or the last open status |

API: `/api/v1/task-statuses` (`tasks.manage-status`).

## Priority administration
Web resource: `task-priorities` (`TaskPriorityController`).

| Concern | Rule |
| --- | --- |
| Levels | Numeric `level` (Low → Critical by default) |
| Default | One default priority (Medium by default) |
| Delete | Cannot delete priorities in use, the default priority, or the last remaining priority |

API: `/api/v1/task-priorities` (`tasks.manage-priority`).

## Permissions
| Permission | Capability |
| --- | --- |
| `tasks.view` | View tasks and most widgets |
| `tasks.create` | Create tasks |
| `tasks.edit` / `tasks.update` | Edit tasks |
| `tasks.delete` | Delete tasks |
| `tasks.archive` / `tasks.restore` | Archive lifecycle |
| `tasks.assign` | Assign users |
| `tasks.comment` | Comments |
| `tasks.attachments` | Attachments |
| `tasks.time-log` | Time logs / timer |
| `tasks.manage-status` | Status catalog |
| `tasks.manage-priority` | Priority catalog |
| `tasks.manage-dependencies` | Dependencies |
| `tasks.manage-checklists` | Checklists |
| `tasks.export` / `tasks.import` | Export/import capability flags |
| `tasks.manage` | Full task management |

`TaskPermissionSeeder` seeds system permissions, clones them per organization, and attaches grants from `config/rbac.php` with `syncWithoutDetaching`.

## Dashboard widgets
Definitions live in `config/dashboard.php`. After config changes, run `TaskWidgetSeeder` (or the dashboard platform seeder).

| Widget Key | Typical permission |
| --- | --- |
| `my_tasks` | `tasks.view` |
| `tasks_due_today` | `tasks.view` |
| `overdue_tasks` | `tasks.view` |
| `recently_updated_tasks` | `tasks.view` |
| `time_logged_today` | `tasks.time-log` |
| `team_task_summary` | `tasks.manage` |

## Metadata fields
Create published field definitions for entity type `task` to extend create/edit/detail forms.

## Dependencies
- Projects (required for work-management create path)
- CRM entities (optional polymorphic taskables)
- Metadata Platform
- Dashboard platform (widgets and quick actions)
- Workflows (task domain triggers)

## Related Documentation
See [lifecycle](lifecycle.md), [architecture](architecture.md), [apis](apis.md), and [developer-guide](developer-guide.md).
