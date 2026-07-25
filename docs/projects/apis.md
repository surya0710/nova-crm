# Projects REST API

## Purpose
Reference for project foundation API endpoints. Routes are provisioned in a subsequent phase; this document reflects the service contract surface.

## Base Path
`/api/v1/projects`

## Authentication and Tenancy
- **Auth:** Sanctum bearer token (`auth:sanctum`)
- **Organization:** `X-Organization-Id` header via organization middleware
- **Permissions:** `projects.view`, `projects.create`, `projects.edit`, `projects.manage`

## Projects
| Method | Path | Permission | Description |
| --- | --- | --- | --- |
| GET | `/projects` | `projects.view` | Paginated project list |
| POST | `/projects` | `projects.create` | Create project |
| GET | `/projects/{project}` | `projects.view` | Single project with relations |
| PUT/PATCH | `/projects/{project}` | `projects.edit` | Update project |
| DELETE | `/projects/{project}` | `projects.manage` | Delete project (non-completed only) |
| POST | `/projects/{project}/archive` | `projects.manage` | Archive project |
| POST | `/projects/{project}/restore` | `projects.manage` | Restore archived project |
| POST | `/projects/{project}/lifecycle` | `projects.edit` | Change lifecycle stage |

## Members
| Method | Path | Permission | Description |
| --- | --- | --- | --- |
| GET | `/projects/{project}/members` | `projects.view` | List members |
| POST | `/projects/{project}/members` | `projects.manage` | Add member |
| PATCH | `/projects/{project}/members/{member}` | `projects.manage` | Update member role |
| DELETE | `/projects/{project}/members/{member}` | `projects.manage` | Remove member |

## Milestones
| Method | Path | Permission | Description |
| --- | --- | --- | --- |
| GET | `/projects/{project}/milestones` | `projects.view` | List milestones |
| POST | `/projects/{project}/milestones` | `projects.edit` | Create milestone |
| PATCH | `/projects/{project}/milestones/{milestone}` | `projects.edit` | Update milestone |
| POST | `/projects/{project}/milestones/{milestone}/complete` | `projects.edit` | Complete milestone |

## Catalogs (Admin)
| Method | Path | Permission | Description |
| --- | --- | --- | --- |
| GET/POST | `/project-categories` | `projects.manage` | Category catalog |
| GET/POST | `/project-types` | `projects.manage` | Type catalog |
| GET/POST | `/project-statuses` | `projects.manage` | Status catalog |
| GET/POST | `/project-lifecycle-stages` | `projects.manage` | Lifecycle stage catalog |

## Dashboard Widget Data
| Method | Path | Permission | Description |
| --- | --- | --- | --- |
| GET | `/dashboard/widgets/my_projects/data` | `projects.view` | My projects widget payload |
| GET | `/dashboard/widgets/active_projects/data` | `projects.view` | Active projects widget payload |
| GET | `/dashboard/widgets/project_deadlines/data` | `projects.view` | Upcoming deadlines widget payload |
| GET | `/dashboard/widgets/project_milestones/data` | `projects.view` | Upcoming milestones widget payload |

## Multi-Tenancy
All queries are organization-scoped. Cross-tenant resource IDs return 404.

## Related Documentation
See [architecture](architecture.md) and [developer-guide](developer-guide.md).
