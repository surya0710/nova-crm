# Project Dependencies

## Overview
Cross-project dependency links for portfolio-level scheduling and impact analysis. Supports classic dependency types with lag days, cycle detection, and a graph view optionally scoped to a portfolio.

## Database Tables
| Table | Model | Purpose |
| --- | --- | --- |
| `project_dependencies` | `ProjectDependency` | Predecessor → successor edge (`dependency_type`, `lag_days`, notes) |

## Services
`DependencyGraphService`:
- `create` / `update` / `delete` — CRUD with type/org validation, no self-link, no cycle
- `graph($organization, ?Portfolio $portfolio)` — `{nodes, edges}` for visualization
- `impactAnalysis($project)` — direct predecessors/successors, upstream/downstream IDs
- `blockingIndicators($project)` — whether incomplete predecessors block the project

**Types** (`DEPENDENCY_TYPES`): `finish_to_start`, `start_to_start`, `finish_to_finish`, `start_to_finish`.

## RBAC Permissions
| Slug | Capability |
| --- | --- |
| `projects.dependencies.view` | View graph and dependencies |
| `projects.dependencies.manage` | Create/update/delete dependencies |

Project policy helpers: `viewDependencies`, `manageDependencies`.

## Workflow Events
| Trigger | Event |
| --- | --- |
| `project.dependency.created` | `ProjectDependencyCreated` |
| `project.dependency.updated` | `ProjectDependencyUpdated` |

Delete fires `ProjectDependencyUpdated` with `deleted=true`.

## API / Routes Summary
| Surface | Routes |
| --- | --- |
| Web | `project-dependencies` index/store/update/destroy; `projects/{project}/dependencies` |
| API | `api/v1/project-dependencies` CRUD; project-scoped impact |

## UI
Blade under `resources/views/project-dependencies/` and project nested views. Graph supports optional `portfolio_id` filter.

## Acceptance Notes
- Both projects must belong to the organization; self-links and cycles are rejected.
- Audit via `Auditable` on `ProjectDependency`.
