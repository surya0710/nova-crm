# Portfolio Management

## Overview
Organization-scoped portfolios group related projects for leadership rollup, health, budget, risk, and forecasting. Each portfolio has a unique code per tenant, an owner, status, optional dates, and a many-to-many project membership.

## Database Tables
| Table | Model | Purpose |
| --- | --- | --- |
| `portfolios` | `Portfolio` | Portfolio catalog (name, code, owner, status, color, dates, metadata/settings) |
| `portfolio_projects` | — | Pivot attaching projects to portfolios |

Unique constraint: `(organization_id, code)` on `portfolios`.

## Services
`PortfolioService`:
- `create` / `update` / `delete` — CRUD; auto-normalizes unique `code`; optional `project_ids` sync
- `archive` — sets `archived_at` and `status=archived` (archived portfolios are read-only)
- `attachProject` / `detachProject` — same-org membership
- `list` / `query` — filters: `search`, `status`, `owner_id`, `archived`, `project_id`

`PortfolioStatisticsService` provides rollup metrics used on show/dashboard (see [portfolio-analytics](portfolio-analytics.md)).

## RBAC Permissions
| Slug | Capability |
| --- | --- |
| `projects.portfolios.view` | List/view portfolios |
| `projects.portfolios.create` | Create portfolios |
| `projects.portfolios.update` | Edit / attach / detach |
| `projects.portfolios.delete` | Delete portfolios |
| `projects.portfolios.manage` | Full portfolio administration |

## Workflow Events
| Trigger | Event |
| --- | --- |
| `portfolio.created` | `PortfolioCreated` |
| `portfolio.updated` | `PortfolioUpdated` |
| `portfolio.deleted` | `PortfolioDeleted` |

## API / Routes Summary
| Surface | Routes |
| --- | --- |
| Web | Resource `portfolios`; `POST archive`; attach/detach projects; `GET portfolios/{portfolio}/dashboard` |
| API | `api/v1/portfolios` resource; archive; projects attach/detach; dashboard; `GET portfolios/{portfolio}/statistics` |

## UI
Blade under `resources/views/portfolios/` (`index`, `create`, `edit`, `show`, `dashboard`). Global search returns portfolios when the user has `projects.portfolios.view` (or `.manage`), linking to `portfolios.show`.

## Acceptance Notes
- Portfolios are tenant-scoped; cross-org IDs return 404.
- Code uniqueness is org-local; empty name is rejected.
- Archived portfolios reject updates via the service.
- Audit via `Auditable` on `Portfolio`.
