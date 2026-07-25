# Project Lifecycle

## Purpose
Describe lifecycle stages and how projects move through delivery phases.

## Lifecycle vs Status
- **Status** — operational state (Draft, Active, Completed, etc.) managed via `project_statuses`
- **Lifecycle stage** — delivery phase (Planning, Execution, Closing, etc.) managed via `project_lifecycle_stages`

Both are organization-scoped catalogs seeded from `config/projects.php`.

## Default Lifecycle Stages
| Sequence | Slug | Name | Description |
| --- | --- | --- | --- |
| 1 | `planning` | Planning | Define scope, objectives, and plan |
| 2 | `initiation` | Initiation | Kick off and mobilize the team |
| 3 | `execution` | Execution | Deliver project work packages |
| 4 | `monitoring` | Monitoring | Track progress and manage risks |
| 5 | `closing` | Closing | Handover, review, and close |

## Default Statuses
| Slug | Closed | Default |
| --- | --- | --- |
| `draft` | No | Yes |
| `planned` | No | |
| `active` | No | |
| `on-hold` | No | |
| `completed` | Yes | |
| `cancelled` | Yes | |
| `archived` | Yes | |

## Transitions
### Lifecycle stage change
`ProjectLifecycleService::changeStage()` updates `lifecycle_stage_id`, validates organization ownership, blocks archived projects, and emits `ProjectLifecycleChanged` with previous and new stage IDs.

### Status change
Status updates occur through `ProjectService::update()` when `status_id` is supplied. Closed statuses (`is_closed = true`) indicate terminal operational states.

### Archive and restore
- **Archive** — sets `is_archived = true`; may align status to `archived` when that catalog entry exists; emits `ProjectArchived`
- **Restore** — clears `is_archived`; emits `ProjectRestored`
- Archived projects are read-only for updates and lifecycle changes

## Seeding
Run `ProjectLifecycleSeeder` or `ProjectFoundationSeeder` to provision default stages per organization. `ProjectDefaultsService::seedLifecycleStages()` is idempotent via `updateOrCreate`.

## Administration
Administrators may add custom stages and statuses. Stages in use or marked default cannot be deleted. At least one open status must remain per organization.

## Related Documentation
See [administrator-guide](administrator-guide.md) and [developer-guide](developer-guide.md).
