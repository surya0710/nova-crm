# Executive Dashboard

## Purpose
Leadership views of delivery health. Phase 12.4 provides the **project executive** portfolio health rollup. Phase 12.6 adds the **portfolio executive** org-wide EPM dashboard (budgets, risks, forecasts, per-portfolio stats).

---

## Phase 12.4 — Project Executive

Portfolio-level view of project health, at-risk counts, and per-project latest health snapshots for leadership.

### Web UI
| Route | Controller | Permission |
| --- | --- | --- |
| `GET projects/executive` | `ProjectExecutiveDashboardController@index` | `projects.view` (viewAny) |

View: `resources/views/projects.executive.index`.

**Page data:**
- `portfolioHealth` — counts by health status (`ProjectHealthService::portfolioSummary`)
- `snapshots` — latest health snapshot per project with project relations
- `activeProjects` — non-archived, non-closed status count
- `atRiskCount` — `at_risk` + `delayed` from portfolio summary

Links from snapshot rows to project Progress Center (`projects.progress.dashboard`).

### API
`GET /api/v1/projects/executive-summary` (`ApiProjectExecutiveController`) — JSON portfolio summary for integrations.

### Dashboard Widgets (progress/health)
Registered in `config/dashboard.php` (section `projects`):

| Widget key | Provider | Permission |
| --- | --- | --- |
| `project_health` | `ProjectHealthWidgetProvider` | `projects.health.view` |
| `projects_at_risk` | `ProjectsAtRiskWidgetProvider` | `projects.health.view` |
| `recently_updated_projects` | `RecentlyUpdatedProjectsWidgetProvider` | `projects.progress.view` |

Widget data via shared dashboard API: `GET /api/v1/dashboard/widgets/{widgetKey}/data`.

### Quick Actions
Dashboard quick actions (config) link to `projects.executive` and report generation for users with `projects.reports.generate`.

---

## Phase 12.6 — Portfolio Executive

Org-wide EPM executive payload built by `ExecutiveDashboardService::forOrganization`.

### Web UI
| Route | Controller | Permission |
| --- | --- | --- |
| `GET portfolios/executive` | `PortfolioExecutiveController@show` | `projects.executive.view` |

View: `resources/views/portfolios/executive/show`.

**Payload highlights:**
- `portfolio_health` — project health summary
- `progress` — average completion and active project count
- `budget_status` — org budget rollup
- At-risk / delayed projects, upcoming milestones, risk overview
- KPIs, department performance, delivery forecast
- Per-portfolio statistics via `PortfolioStatisticsService`

### API
`GET /api/v1/portfolios/executive` (`Api\ExecutiveDashboardController`) — JSON executive payload.

### Dashboard Widgets (EPM)
| Widget key | Provider | Permission |
| --- | --- | --- |
| `executive_summary` | `ExecutiveSummaryWidgetProvider` | `projects.executive.view` |
| `portfolio_overview` | `PortfolioOverviewWidgetProvider` | `projects.portfolios.view` |
| `portfolio_health_epm` | `PortfolioHealthEpmWidgetProvider` | `projects.portfolios.view` |

### RBAC
| Slug | Capability |
| --- | --- |
| `projects.executive.view` | Access portfolio executive dashboard |

### Reporting
Executive report type (`executive`) is available via `PortfolioReportingService` / `portfolio-reports` routes.

---

## Related Documentation
See [project-health](project-health.md), [progress-tracking](progress-tracking.md), [portfolio-analytics](portfolio-analytics.md), [forecasting](forecasting.md), and [P12_PHASE_12_6_PORTFOLIO](../P12_PHASE_12_6_PORTFOLIO.md).
