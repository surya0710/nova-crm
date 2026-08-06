# Portfolio Analytics

## Overview
Rollup analytics for a portfolio: average progress, health distribution, budget summary, project counts by status, and open-risk score. Powers portfolio show/dashboard, widgets, and report payloads. Optional dispatch of `PortfolioHealthUpdated` when statistics are refreshed.

## Services
`PortfolioStatisticsService`:
- `forPortfolio($portfolio, ?$actor, $dispatchHealthEvent = false)` — full stats payload
- `averageProgress($projects)` — average completion %
- `healthRollup($portfolio, $projects)` — counts by health status (snapshots or live)
- `budgetSummary($portfolio, $projects)` — planned/actual/forecast rollup from `ProjectBudget` or project fields
- `projectCountsByStatus($projects)` — counts by project status slug
- `riskScore($portfolio)` — average open risk severity (0–25)

**`forPortfolio` keys:** `portfolio_id`, `project_count`, `average_completion_percentage`, `health`, `budget`, `projects_by_status`, `risk_score`.

`PortfolioReportingService`:
- `generate($organization, $reportType, $format, $filters, $actor, ?$portfolio, ?$program)` — builds payload, exports pdf/excel/csv, persists `PortfolioReport`, notifies actor

**Report types:** `portfolio`, `program`, `risk`, `budget`, `executive`, `variance`, `forecast`.

## RBAC Permissions
| Slug | Capability |
| --- | --- |
| `projects.portfolios.view` | View portfolio dashboards/statistics |
| `projects.portfolio_reports.view` | List/view reports |
| `projects.portfolio_reports.generate` | Generate / download reports |

## Workflow Events
| Trigger | Event |
| --- | --- |
| `portfolio.health.updated` | `PortfolioHealthUpdated` |
| `portfolio.report.generated` | `PortfolioReportGenerated` |

## API / Routes Summary
| Surface | Routes |
| --- | --- |
| Web | Portfolio dashboard; `portfolio-reports` index/store/download |
| API | `GET portfolios/{portfolio}/statistics` (`dispatch_health_event=true` optional); `portfolio-reports` |

## UI
Portfolio dashboard and report center Blade views. Widgets: portfolio overview, portfolio health (EPM). Global search matches portfolio reports with `projects.portfolio_reports.view`.

## Acceptance Notes
- Statistics are read-only aggregations; they do not mutate projects.
- Report generation uses Storage; tests should fake the disk.
- Audit via `Auditable` on `PortfolioReport`.
