# Risk Register

## Overview
Organization-scoped risk register attachable to a project, portfolio, and/or program. Probability and impact (1–5) produce severity; a 5×5 heatmap supports prioritization. Escalation records history and notifies stakeholders.

## Database Tables
| Table | Model | Purpose |
| --- | --- | --- |
| `project_risks` | `ProjectRisk` | Risk records (title, category, scores, plans, status, history, escalation) |

## Services
`RiskManagementService`:
- `create` / `update` / `delete` — CRUD; clamps scores 1–5; severity = probability × impact; history on create/status change
- `escalate($risk, $actor, ?$note)` — sets `status=escalated`, `escalated_at`; notifies; fires escalate event (service-level; no dedicated HTTP route yet)
- `matrix($organization, ?$projectId, ?$portfolioId)` — `{matrix, cells}` heatmap (excludes `closed`, `accepted`)
- `list` — filters: `project_id`, `portfolio_id`, `program_id`, `status`; ordered by severity desc

## RBAC Permissions
| Slug | Capability |
| --- | --- |
| `projects.risks.view` | List/view risks and matrix |
| `projects.risks.create` | Create risks |
| `projects.risks.update` | Edit risks |
| `projects.risks.delete` | Delete risks |
| `projects.risks.manage` | Full risk administration |

Project helpers: `viewRisks`, `createRisks`, `updateRisks`.

## Workflow Events
| Trigger | Event |
| --- | --- |
| `project.risk.created` | `ProjectRiskCreated` |
| `project.risk.updated` | `ProjectRiskUpdated` |
| `project.risk.escalated` | `ProjectRiskEscalated` |

## API / Routes Summary
| Surface | Routes |
| --- | --- |
| Web | Org `risks` index/store/update/destroy; nested `projects/{project}/risks` |
| API | `api/v1/risks` and nested project risks |

## UI
Blade under `resources/views/risks/` and `resources/views/projects/risks/`. Dashboard widgets: risk heatmap, upcoming risks. Global search matches title/description/category with `projects.risks.view`.

## Acceptance Notes
- Risks are tenant-scoped; optional project/portfolio/program must match org.
- Severity is always recomputed from probability × impact.
- Audit via `Auditable` on `ProjectRisk`.
