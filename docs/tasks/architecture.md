# Task Architecture

## Purpose
Technical map of the Task & Work Management module (Phase 12.2) and its integrations with Projects, CRM, Metadata, RBAC, and Workflows.

## Layers
```
Controller → Form Request → Task / Checklist / Time / Dependency Service → Models
Controller → Catalog Service → Task Status / Priority Models
Dashboard → Widget Provider → Tenant-Scoped Queries
```

## Multi-Tenancy
All task tables include `organization_id`. Models use `BelongsToOrganization` and `OrganizationScope`. Services and controllers rely on `TenantContext`. Cross-tenant IDs return 404.

Work-management creates require a project in the same organization (`TaskService::createWorkManagement`). Legacy CRM tasks may still attach polymorphically via `taskable` without a project.

## Database Tables
| Table | Purpose |
| --- | --- |
| `tasks` | Core task record (legacy CRM columns plus work-management columns) |
| `task_statuses` | Organization status catalog (`is_default`, `is_closed`, `sort_order`) |
| `task_priorities` | Organization priority catalog (`level`, `is_default`) |
| `task_dependencies` | Predecessor → successor edges with dependency type |
| `task_checklists` | Ordered checklist items on a task |
| `task_comments` | Comments with optional parent reply |
| `task_attachments` | Uploaded files linked to a task |
| `task_time_logs` | Manual / timer / import-sourced time entries |

### Notable `tasks` columns
| Column | Role |
| --- | --- |
| `project_id`, `milestone_id` | Project foundation linkage |
| `parent_task_id` | Subtask hierarchy |
| `status_id` / `priority_id` | Catalog FKs (synced with legacy string `status` / `priority`) |
| `task_number`, `slug` | Org-unique identifiers (`TASK-0001` style) |
| `estimated_hours`, `actual_hours` | Effort estimates and rolled-up logged time |
| `completion_percentage` | Progress (checklist/children driven) |
| `metadata`, `settings` | Custom fields + reserved settings JSON |
| `is_archived` | Soft archive / read-only gate |

## Services
| Service | Responsibility |
| --- | --- |
| `TaskDefaultsService` | Seed default statuses and priorities per organization |
| `TaskService` | CRUD, assign, complete, archive/restore, numbering, progress, metadata |
| `TaskStatusService` | Status catalog management |
| `TaskPriorityService` | Priority catalog management |
| `TaskDependencyService` | Dependency create/delete, cycle detection, graph |
| `ChecklistService` | Checklist CRUD, reorder, complete, progress refresh |
| `TimeTrackingService` | Manual log, timer start/stop, actual hours refresh |
| `TaskCommentService` | Comments, mentions, `CommentAdded` |
| `TaskAttachmentService` | Upload, download, delete |
| `TaskStatisticsService` | Org/task aggregates for dashboards and detail |

## RBAC
Organization permissions (`tasks.view`, `tasks.create`, `tasks.edit` / `tasks.update`, `tasks.delete`, `tasks.archive`, `tasks.restore`, `tasks.assign`, `tasks.comment`, `tasks.attachments`, `tasks.time-log`, `tasks.manage-status`, `tasks.manage-priority`, `tasks.manage-dependencies`, `tasks.manage-checklists`, `tasks.export`, `tasks.import`, `tasks.manage`) gate module access. Policies live on `TaskPolicy` and related model policies.

## Metadata
Tasks persist custom field values in JSON `metadata` and expose a `custom_fields` attribute alias. Metadata Platform entity key is `task` (via `MetadataEntityFormService`).

## Events
| Event | Trigger |
| --- | --- |
| `TaskCreated` | Task created |
| `TaskUpdated` | Task fields or metadata changed |
| `TaskAssigned` / `TaskReassigned` | Assignee set or changed |
| `TaskStarted` | Status slug moves to `in-progress` |
| `TaskCompleted` | Status slug moves to `completed` |
| `TaskArchived` / `TaskRestored` | Archive flag toggled |
| `DependencyCreated` / `DependencyRemoved` | Dependency edge added/removed |
| `ChecklistCompleted` | Checklist item marked complete |
| `CommentAdded` | Comment created |
| `TimeLogged` | Completed time log recorded |

Workflow triggers are registered in `config/workflows.php` under `task.*` keys.

## Dashboard Widgets
Widget providers under `App\Services\Dashboard\Widgets` (e.g. `MyTasksWidgetProvider`, `TasksDueTodayWidgetProvider`, `OverdueTasksWidgetProvider`, `RecentlyUpdatedTasksWidgetProvider`, `TimeLoggedTodayWidgetProvider`, `TeamTaskSummaryWidgetProvider`) register via `config/dashboard.php` and `TaskWidgetSeeder`.

## Audit
`Task` uses the `Auditable` concern for change tracking.

## Future foundations (reserved, not implemented)
The schema and config intentionally leave room for later work **without** shipping these features in Phase 12.2:

| Reserved idea | Current foundation |
| --- | --- |
| Sprints / iterations | `settings` JSON; no sprint tables |
| Epics / themes | `parent_task_id` hierarchy only; no epic entity |
| Story points | `estimated_hours` exists; no points field or velocity |
| Gantt / scheduling engine | Dependencies + dates exist; no Gantt UI or critical-path solver |
| Resource leveling | Time logs exist; no capacity planner |

Do not invent endpoints or tables for these until a later phase implements them.

## Extension Points
- Workflow listeners on task domain events
- Metadata field definitions for entity `task`
- Additional widget providers in dashboard config
- Catalog customization via status/priority admin UI

## Related Documentation
See [lifecycle](lifecycle.md), [dependencies](dependencies.md), [time-tracking](time-tracking.md), [checklists](checklists.md), [apis](apis.md), and [developer-guide](developer-guide.md).
