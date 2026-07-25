# Progress Tracking

## Purpose
Organization-scoped manual progress updates for projects: percentage, summary, blockers, next steps, optional milestone link, and metadata. Updates append to history (never overwrite prior rows).

## Data Model
| Table | Model | Purpose |
| --- | --- | --- |
| `progress_updates` | `ProgressUpdate` | Append-only progress history per project |

Key fields: `progress_percentage` (0–100), `summary` (required), `blockers`, `next_steps`, `milestone_id`, `updated_by`, `metadata` (custom fields via entity `project_progress_update`).

Creating an update also sets `projects.completion_percentage` to the latest percentage (manual component in health calculation).

## Service
`ProgressTrackingService`:
- `list($project, $perPage)` — paginated history (newest first)
- `create($project, $data, $actor)` — validate, persist, sync project completion, dispatch `ProgressUpdated`, notify owner/manager
- `update($update, $data, $actor)` — edit existing row (does not create history entry)
- `delete($update, $actor)` — remove row; archived projects are read-only

Validation: percentage 0–100; non-empty summary; archived projects rejected.

## Permissions
| Slug | Capability |
| --- | --- |
| `projects.progress.view` | List progress history, Progress Center |
| `projects.progress.create` | Post new updates |
| `projects.progress.update` | Edit existing updates |
| `projects.progress.delete` | Delete updates |

Policy methods: `viewProgress`, `createProgress`, `updateProgress`, `deleteProgress` on `ProjectPolicy`.

## UI — Progress Center
| Route | Name | Description |
| --- | --- | --- |
| `GET projects/{project}/progress` | `projects.progress.index` | History list + create/edit forms |
| `GET projects/{project}/progress/dashboard` | `projects.progress.dashboard` | Progress Center (health, stats, milestones, timeline) |

Blade: `resources/views/projects/progress/index.blade.php`, `resources/views/projects/progress/dashboard.blade.php`.

## Events & Audit
- **Event:** `ProgressUpdated` → workflow trigger `project.progress.updated`
- **Audit:** `ProgressUpdate` uses `Auditable` concern
- **Search:** `SearchService::searchProgressUpdates()` when user has `projects.progress.view`

## Related Documentation
See [project-health](project-health.md), [progress-api](progress-api.md), and [progress-user-guide](progress-user-guide.md).
