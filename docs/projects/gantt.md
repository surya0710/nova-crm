# Gantt & Timeline

## Purpose
Unified project timeline (tasks, milestones, dependencies, allocations) and Gantt chart data for UI and API consumers.

## Service
`TimelineService` (`App\Services\TimelineService`):

### `build($project)`
Returns structured timeline:

| Key | Contents |
| --- | --- |
| `project` | id, name, dates, completion_percentage |
| `milestones` | id, name, due_date, status, sequence |
| `tasks` | id, title, dates, completion_percentage, status, milestone_id |
| `dependencies` | predecessor/successor task IDs, dependency_type |
| `resource_allocations` | employee, dates, hours, allocation % |

### `gantt($project)`
Returns flat list of Gantt bars:

| Field | Description |
| --- | --- |
| `id` | Prefixed `project-`, `milestone-`, or `task-{id}` |
| `type` | `project`, `milestone`, or `task` |
| `name`, `start`, `end` | Bar label and date range |
| `progress` | 0–100 |
| `status` | Task/milestone/project status |
| `dependencies` | List of predecessor bar IDs (tasks) |
| `color` | Hex by type (project green, milestone indigo, task sky) |

Project bar requires `start_date` and `planned_end_date`. Milestone/task bars infer start/end from due dates or created_at.

### Other methods
- `criticalMilestones($project)` — open milestones ordered by due date (executive reports)
- `publishUpdate($project, $actor?)` — rebuild + dispatch `TimelineUpdated`

## Permissions
| Slug | Capability |
| --- | --- |
| `projects.timeline.view` | Timeline data |
| `projects.gantt.view` | Gantt data |

## UI
| Route | Description |
| --- | --- |
| `GET projects/{project}/gantt` | Gantt chart page (`ProjectGanttController`) |
| `GET projects/{project}/timeline` | Legacy timeline view on project controller |

Progress Center dashboard embeds timeline via `TimelineService::build()`.

## API
See [progress-api](progress-api.md): `GET .../timeline`, `GET .../gantt`.

## Related Documentation
See [progress-architecture](progress-architecture.md) and [reporting](reporting.md).
