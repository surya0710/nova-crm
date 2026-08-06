# Project Labels

## Overview
Organization-scoped colored labels for tagging tasks. Labels are catalogued once per organization and attached to many tasks via a pivot. System defaults (Urgent, Backend, Bug, etc.) can be seeded per tenant.

## Database Tables
| Table | Model | Purpose |
| --- | --- | --- |
| `project_labels` | `ProjectLabel` | Org label catalog (name, color, description, `is_system`) |
| `task_labels` | `TaskLabel` | Pivot attaching labels to tasks |

Unique constraint: `(organization_id, name)` on `project_labels`; `(task_id, label_id)` on `task_labels`.

## Services
`ProjectLabelService`:
- `create` / `update` / `delete` — CRUD with uniqueness checks; system labels protected from casual delete
- `attach($task, $label, $actor)` / `detach(...)` — pivot management
- `list($organization, $filters)` — optional search filter
- `seedDefaults($organization)` — inserts `DEFAULT_LABELS` when missing

## RBAC Permissions
| Slug | Capability |
| --- | --- |
| `projects.labels.view` | List labels |
| `projects.labels.create` | Create labels |
| `projects.labels.update` | Edit labels |
| `projects.labels.delete` | Delete labels |
| `projects.labels.manage` | Full label administration (includes attach/detach where gated) |

## Workflow Events
| Trigger | Event |
| --- | --- |
| `project.label.created` | `ProjectLabelCreated` |
| `project.label.updated` | `ProjectLabelUpdated` |
| `project.label.deleted` | `ProjectLabelDeleted` |
| `task.label.attached` | `TaskLabelAttached` |
| `task.label.detached` | `TaskLabelDetached` |

## API / Routes Summary
| Surface | Routes |
| --- | --- |
| Web | Resource `project-labels` (`index`, `create`, `store`, `edit`, `update`, `destroy`); attach/detach on tasks |
| API | `api/project-labels` resource; `POST/DELETE tasks/{task}/labels/{label}` |

## UI
Blade under `resources/views/projects/labels/` (`index`, `create`, `edit`, `_form`). Global search returns labels when the user has `projects.labels.view` (or `.manage`), linking to `project-labels.index`.

## Acceptance Notes
- Labels are tenant-scoped; cross-org IDs return 404.
- Default seed is idempotent (skips existing names).
- Attaching the same label twice is a no-op / unique pivot.
- Audit via `Auditable` on `ProjectLabel`.
