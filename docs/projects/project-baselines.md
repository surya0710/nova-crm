# Project Baselines

## Overview
Baselines snapshot a project's scope (milestones/tasks), schedule, budget fields, and progress at a point in time. Versions auto-increment per project. Comparison and variance analysis measure drift from the captured baseline.

## Database Tables
| Table | Model | Purpose |
| --- | --- | --- |
| `project_baselines` | `ProjectBaseline` | Versioned snapshots (`scope_snapshot`, `schedule_snapshot`, `budget_snapshot`, `progress_snapshot`) |

## Services
`BaselineService`:
- `capture($project, $actor, ?$notes, ?$name)` — creates next version; notifies actor; fires created event
- `compare($baseline, ?$project)` — raw deltas (scope counts, schedule drift days, budget, progress)

`VarianceAnalysisService`:
- `forProject($project, ?$baseline)` — uses latest baseline if omitted; `null` when none exist
- `compare($baseline, ?$project)` — enhanced variance with drift percents and flags (`schedule_slip`, `budget_overrun`, `scope_creep`, `scope_reduction`, `progress_behind`)

## RBAC Permissions
| Slug | Capability |
| --- | --- |
| `projects.baselines.view` | List/view baselines and comparisons |
| `projects.baselines.create` | Capture baselines |
| `projects.baselines.manage` | Full baseline administration |

Project helpers: `viewBaselines`, `createBaselines`.

## Workflow Events
| Trigger | Event |
| --- | --- |
| `project.baseline.created` | `ProjectBaselineCreated` |

## API / Routes Summary
| Surface | Routes |
| --- | --- |
| Web | `projects/{project}/baselines` index/store/show |
| API | `api/v1/projects/{project}/baselines` index/store/show |

## UI
Blade under `resources/views/projects/baselines/`. Show view includes `BaselineService::compare` output. Global search matches baseline names with `projects.baselines.view`.

## Acceptance Notes
- Baselines are immutable snapshots after capture (no update API).
- Variance reporting type uses `VarianceAnalysisService` via `PortfolioReportingService`.
- Audit via `Auditable` on `ProjectBaseline`.
