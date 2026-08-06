# Task Dependencies

## Purpose
Describe predecessor/successor relationships, dependency types, and circular-dependency prevention.

## Model
`task_dependencies` stores directed edges:

| Field | Meaning |
| --- | --- |
| `predecessor_task_id` | Upstream task |
| `successor_task_id` | Downstream task (the route `{task}` when creating via API/web) |
| `dependency_type` | Scheduling relationship |
| `organization_id` | Tenant scope |

Unique on `(predecessor_task_id, successor_task_id)`. Both tasks must share an organization.

## Dependency types
Configured in `config/tasks.php` as `dependency_types`:

| Key | Label | Common shorthand |
| --- | --- | --- |
| `finish_to_start` | Finish to Start | FS (default) |
| `start_to_start` | Start to Start | SS |
| `finish_to_finish` | Finish to Finish | FF |
| `start_to_finish` | Start to Finish | SF |

Phase 12.2 stores and validates these types. It does **not** implement a Gantt scheduler or automatic date shifting from dependency type semantics.

## Create flow
`TaskDependencyService::create($predecessor, $successor, $data, $actor)`:
1. Rejects cross-organization pairs
2. Rejects self-dependencies
3. Validates `dependency_type` against config
4. Rejects edges that would create a cycle
5. Rejects duplicate predecessor/successor pairs
6. Persists the edge and emits `DependencyCreated`

When creating via HTTP, the route task is the **successor**; the body supplies `predecessor_task_id` and optional `dependency_type`.

## Circular prevention
`wouldCreateCycle()` treats edges as predecessor → successor. Adding predecessor → successor is rejected if the successor can already reach the predecessor (DFS over the org adjacency list).

Self-edges are rejected separately before cycle checks.

## Graph helper
`dependencyGraph($organizationId, $projectId = null)` returns node IDs and typed edges for an organization (optionally filtered to a project). Useful for future timeline/Gantt UIs; no dedicated HTTP graph endpoint ships in this phase.

## Routes
| Surface | List | Create | Delete |
| --- | --- | --- | --- |
| Web | `GET tasks/{task}/dependencies` (`tasks.dependencies.index`) | `POST …` (`tasks.dependencies.store`) | `DELETE …/{dependency}` (`tasks.dependencies.destroy`) |
| API | `GET /api/v1/tasks/{task}/dependencies` | `POST …` | `DELETE …/{dependency}` |

Permission: `tasks.manage-dependencies` (via `TaskPolicy::manageDependencies` / dependency policies).

## Related Documentation
See [architecture](architecture.md), [lifecycle](lifecycle.md), and [apis](apis.md).
