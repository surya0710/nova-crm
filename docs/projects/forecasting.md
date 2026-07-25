# Forecasting

## Overview
Predictive views for project and portfolio delivery: estimated completion, likely schedule delay, budget overrun likelihood, risk outlook, and portfolio capacity pressure. Forecasts optionally dispatch `ForecastGenerated` for workflows.

## Services
`ForecastService`:
- `forProject($project, ?$actor, $dispatch = true)` — project forecast payload
- `forPortfolio($portfolio, ?$actor, $dispatch = true)` — per-project forecasts + capacity metrics
- `estimatedCompletion($project)` — health prediction, velocity heuristic, or linear extrapolation
- `likelyDelay($project)` — `{is_likely, days, estimated_completion, planned_end_date}`
- `budgetOverrun($project)` — uses latest `ProjectBudget` or project budget fields
- `riskForecast($project)` — `{score, open_count, high_severity_count, outlook}` (`critical`/`elevated`/`moderate`/`low`)
- `portfolioCapacity($portfolio, ?$projects)` — resource allocation rollup; `capacity_pressure`: overallocated/tight/balanced/available

## RBAC Permissions
| Slug | Capability |
| --- | --- |
| `projects.forecasts.view` | View project/portfolio forecasts |

Project helper: `viewForecasts`.

## Workflow Events
| Trigger | Event |
| --- | --- |
| `forecast.generated` | `ForecastGenerated` |

## API / Routes Summary
| Surface | Routes |
| --- | --- |
| Web | `portfolios/forecasts` index; `portfolios/forecasts/{portfolio}` show |
| API | `api/v1/portfolios/forecasts` index/show |

## UI
Blade under `resources/views/portfolios/forecasts/`. Dashboard widget: portfolio forecast.

## Acceptance Notes
- Pass `$dispatch = false` when composing nested forecasts (portfolio aggregates project forecasts without duplicate events).
- Forecast quality depends on health snapshots, budgets, risks, and resource allocations being present.
- Forecast report type is available via `PortfolioReportingService`.
