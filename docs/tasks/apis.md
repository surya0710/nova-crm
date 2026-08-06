# Tasks REST API

## Purpose
Reference for Task & Work Management API endpoints as registered in `routes/api.php`.

## Base Path
`/api/v1`

## Authentication and Tenancy
- **Auth:** Sanctum bearer token (`auth:sanctum`)
- **Organization:** `X-Organization-Id` header via `set.organization` / `ensure.organization` / `organization.api`
- **API gate:** `permission:api.access` middleware group
- **Permissions:** fine-grained `tasks.*` slugs enforced by policies on each action

## Tasks
| Method | Path | Permission (typical) | Description |
| --- | --- | --- | --- |
| GET | `/tasks` | `tasks.view` | Paginated list (filters: search, status, status_id, priority, priority_id, assigned_to, project_id, is_archived, filter=`overdue`\|`due_today`) |
| POST | `/tasks` | `tasks.create` | Create task |
| GET | `/tasks/{task}` | `tasks.view` | Show task |
| PUT/PATCH | `/tasks/{task}` | `tasks.edit` / `tasks.update` | Update task |
| DELETE | `/tasks/{task}` | `tasks.delete` | Delete task |
| POST | `/tasks/{task}/archive` | `tasks.archive` | Archive |
| POST | `/tasks/{task}/restore` | `tasks.restore` | Restore |
| POST | `/tasks/{task}/assign` | `tasks.assign` | Assign |
| POST | `/tasks/{task}/complete` | `tasks.edit` / `tasks.update` | Complete |

## Statuses and priorities
`Route::apiResource` under names `api.task-statuses` and `api.task-priorities`:

| Resource | Methods | Permission |
| --- | --- | --- |
| `/task-statuses` | index, store, show, update, destroy | `tasks.manage-status` |
| `/task-priorities` | index, store, show, update, destroy | `tasks.manage-priority` |

Parameter names: `{status}`, `{priority}`.

## Dependencies
| Method | Path | Permission | Description |
| --- | --- | --- | --- |
| GET | `/tasks/{task}/dependencies` | `tasks.view` | List edges involving the task |
| POST | `/tasks/{task}/dependencies` | `tasks.manage-dependencies` | Create (body: `predecessor_task_id`, optional `dependency_type`; route task is successor) |
| DELETE | `/tasks/{task}/dependencies/{dependency}` | `tasks.manage-dependencies` | Remove |

## Checklists
| Method | Path | Permission | Description |
| --- | --- | --- | --- |
| GET | `/tasks/{task}/checklists` | `tasks.view` | List |
| POST | `/tasks/{task}/checklists` | `tasks.manage-checklists` | Create |
| PATCH | `/tasks/{task}/checklists/{checklist}` | `tasks.manage-checklists` | Update |
| DELETE | `/tasks/{task}/checklists/{checklist}` | `tasks.manage-checklists` | Delete |
| POST | `/tasks/{task}/checklists/{checklist}/complete` | `tasks.manage-checklists` | Complete |

## Comments
| Method | Path | Permission | Description |
| --- | --- | --- | --- |
| GET | `/tasks/{task}/comments` | `tasks.view` | List |
| POST | `/tasks/{task}/comments` | `tasks.comment` | Create |
| PATCH | `/tasks/{task}/comments/{comment}` | `tasks.comment` | Update |
| DELETE | `/tasks/{task}/comments/{comment}` | `tasks.comment` | Delete |

## Attachments
| Method | Path | Permission | Description |
| --- | --- | --- | --- |
| GET | `/tasks/{task}/attachments` | `tasks.view` | List |
| POST | `/tasks/{task}/attachments` | `tasks.attachments` | Upload |
| GET | `/tasks/{task}/attachments/{attachment}/download` | `tasks.view` | Download |
| DELETE | `/tasks/{task}/attachments/{attachment}` | `tasks.attachments` | Delete |

## Time logs
| Method | Path | Permission | Description |
| --- | --- | --- | --- |
| GET | `/tasks/{task}/time-logs` | `tasks.view` | List |
| POST | `/tasks/{task}/time-logs` | `tasks.time-log` | Manual log |
| POST | `/tasks/{task}/time-logs/start` | `tasks.time-log` | Start timer |
| POST | `/tasks/{task}/time-logs/stop` | `tasks.time-log` | Stop timer |
| DELETE | `/tasks/{task}/time-logs/{time_log}` | `tasks.time-log` | Delete |

## Dashboard widget data
Task widgets are served by the shared dashboard API (`GET /api/v1/dashboard/widgets/{widgetKey}/data`), not dedicated `/tasks/...` paths. Keys include `my_tasks`, `tasks_due_today`, `overdue_tasks`, `recently_updated_tasks`, `time_logged_today`, `team_task_summary`.

## Multi-Tenancy
All queries are organization-scoped. Cross-tenant resource IDs return 404.

## Related Documentation
See [architecture](architecture.md), [lifecycle](lifecycle.md), and [developer-guide](developer-guide.md).
