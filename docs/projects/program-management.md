# Program Management

## Overview
Programs sit under (optional) portfolios and group related projects for coordinated delivery. Each program has a unique code per organization, a manager, status, and many-to-many project membership.

## Database Tables
| Table | Model | Purpose |
| --- | --- | --- |
| `programs` | `Program` | Program catalog (name, code, portfolio, manager, status, dates, metadata) |
| `program_projects` | — | Pivot attaching projects to programs |

Unique constraint: `(organization_id, code)` on `programs`.

## Services
`ProgramService`:
- `create` / `update` / `delete` — CRUD; validates optional `portfolio_id` belongs to org; syncs `project_ids`
- `attachProject` / `detachProject` — same-org membership
- `list` / `query` — filters: `search`, `status`, `portfolio_id`, `manager_id`

## RBAC Permissions
| Slug | Capability |
| --- | --- |
| `projects.programs.view` | List/view programs |
| `projects.programs.create` | Create programs |
| `projects.programs.update` | Edit / attach / detach |
| `projects.programs.delete` | Delete programs |
| `projects.programs.manage` | Full program administration |

## Workflow Events
| Trigger | Event |
| --- | --- |
| `program.created` | `ProgramCreated` |
| `program.updated` | `ProgramUpdated` |

Delete fires `ProgramUpdated` with `deleted=true` (no separate deleted event).

## API / Routes Summary
| Surface | Routes |
| --- | --- |
| Web | Resource `programs`; dashboard; attach/detach projects |
| API | `api/v1/programs` resource; dashboard; projects attach/detach |

## UI
Blade under `resources/views/programs/` (`index`, `create`, `edit`, `show`, `dashboard`). Global search returns programs when the user has `projects.programs.view` (or `.manage`).

## Acceptance Notes
- Programs are tenant-scoped; portfolio must belong to the same organization.
- Cross-org IDs return 404.
- Audit via `Auditable` on `Program`.
