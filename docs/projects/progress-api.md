# Progress & Reporting REST API

## Purpose
Reference for Phase 12.4 project progress, health, reporting, timeline, and statistics endpoints in `routes/api.php`.

## Base Path
`/api/v1`

## Authentication and Tenancy
- **Auth:** Sanctum bearer token (`auth:sanctum`)
- **Organization:** `X-Organization-Id` header
- **Gate:** `permission:api.access` on the API group
- **Authorization:** `ProjectPolicy` methods per endpoint

## Health
| Method | Path | Policy | Description |
| --- | --- | --- | --- |
| GET | `/projects/{project}/health` | `viewHealth` | Latest snapshot; `?recalculate=1` forces recalculation |

**Response:** `ProjectHealthSnapshotResource` (status, completion %, variances, estimated completion, metadata).

## Progress updates
| Method | Path | Policy | Description |
| --- | --- | --- | --- |
| GET | `/projects/{project}/progress` | `viewProgress` | Paginated history (`per_page`, default 15) |
| POST | `/projects/{project}/progress` | `createProgress` | Create update |
| PUT/PATCH | `/projects/{project}/progress/{progressUpdate}` | `updateProgress` | Update row |
| DELETE | `/projects/{project}/progress/{progressUpdate}` | `deleteProgress` | Delete row |

**Create body:** `progress_percentage` (0–100), `summary` (required), optional `blockers`, `next_steps`, `milestone_id`, `metadata` / `custom_fields`.

**Response:** `ProgressUpdateResource` with `updater`, `milestone`, `project`.

## Reports
| Method | Path | Policy | Description |
| --- | --- | --- | --- |
| GET | `/projects/{project}/reports` | `viewReports` | Paginated report list |
| POST | `/projects/{project}/reports` | `generateReports` | Generate report |

**Generate body:** `report_type`, `format` (`pdf`/`excel`/`csv`), optional `filters` (array).

**Response:** `ProjectReportResource` (201) with `storage_path`, `report_type`, `generated_at`.

## Statistics
| Method | Path | Policy | Description |
| --- | --- | --- | --- |
| GET | `/projects/{project}/statistics` | `viewStatistics` | Project task stats, velocity, productivity |

**Response:** `{ "data": { "tasks": {...}, "velocity": {...}, ... } }`

## Timeline & Gantt
| Method | Path | Policy | Description |
| --- | --- | --- | --- |
| GET | `/projects/{project}/timeline` | `viewTimeline` | Full timeline payload |
| GET | `/projects/{project}/gantt` | `viewGantt` | Gantt bar array |

## Milestone progress
| Method | Path | Policy | Description |
| --- | --- | --- | --- |
| GET | `/projects/{project}/milestones/progress` | `viewProgress` | Per-milestone planned/actual/delay metrics |

## Executive summary
| Method | Path | Permission | Description |
| --- | --- | --- | --- |
| GET | `/projects/executive-summary` | `projects.view` | Portfolio health counts |

## Multi-Tenancy
All models use `organization_id` and organization scope. Cross-tenant project or progress IDs return 404/403.

## Related Documentation
See [progress-tracking](progress-tracking.md), [project-health](project-health.md), [reporting](reporting.md), and [gantt](gantt.md).
