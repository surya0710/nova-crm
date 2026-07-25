# P12 Phase 12.6 — Portfolio Management (EPM)

## Phase
Phase 12.6 — Enterprise Portfolio Management (portfolios, programs, cross-project dependencies, risk/issue registers, baselines, budgeting, forecasts, portfolio analytics, executive dashboard, reporting)

## Outcome
Organization-scoped EPM on top of Projects: portfolios and programs with project membership, dependency graphs with cycle detection, risk heatmaps and issue registers, baseline capture/variance, project budgets with category line items, project/portfolio forecasting, portfolio statistics and downloadable reports, portfolio executive dashboard, RBAC, dashboard widgets, REST APIs, Blade UI, global search integration, documentation, and tests.

## Delivered

| Area | Status |
| --- | --- |
| Tables (`portfolios`, `portfolio_projects`, `programs`, `program_projects`, `project_dependencies`, `project_risks`, `project_issues`, `project_baselines`, `budget_categories`, `project_budgets`, `budget_items`, `portfolio_reports`) | Done |
| Models + services (portfolio, program, dependency graph, risk/issue, baseline, budget, forecast, portfolio statistics, variance, executive dashboard, portfolio reporting) | Done |
| Domain events + workflow triggers (`portfolio.*`, `program.*`, `project.dependency.*`, `project.risk.*`, `project.issue.*`, `project.baseline.created`, `project.budget.updated`, `portfolio.health.updated`, `portfolio.report.generated`, `forecast.generated`) | Done |
| RBAC (`projects.portfolios.*`, `projects.programs.*`, `projects.dependencies.*`, `projects.risks.*`, `projects.issues.*`, `projects.baselines.*`, `projects.budgets.*`, `projects.forecasts.view`, `projects.executive.view`, `projects.portfolio_reports.*`) | Done |
| Dashboard widgets + quick actions | Done |
| Web + API controllers/routes | Done |
| Blade UI (portfolios, programs, dependencies, risks, issues, baselines, budgets, forecasts, executive, reports) | Done |
| Seeders (`ProjectPortfolioSeeder` and related) | Done |
| Global search (portfolios, programs, risks, issues, baselines, budgets, portfolio reports) | Done |
| Documentation (`docs/projects/*` EPM guides + this phase file) | Done |

## Feature documentation

| Topic | Doc |
| --- | --- |
| Portfolios | [portfolio-management.md](projects/portfolio-management.md) |
| Programs | [program-management.md](projects/program-management.md) |
| Dependencies | [project-dependencies.md](projects/project-dependencies.md) |
| Risk register | [risk-register.md](projects/risk-register.md) |
| Issue register | [issue-register.md](projects/issue-register.md) |
| Baselines | [project-baselines.md](projects/project-baselines.md) |
| Budgeting | [project-budgeting.md](projects/project-budgeting.md) |
| Portfolio analytics | [portfolio-analytics.md](projects/portfolio-analytics.md) |
| Forecasting | [forecasting.md](projects/forecasting.md) |
| Executive dashboard | [executive-dashboard.md](projects/executive-dashboard.md) |

Also see [projects/overview.md](projects/overview.md) and [projects/architecture.md](projects/architecture.md) for Phase 12.6 pointers.

## Run

```bash
php artisan migrate
php artisan db:seed --class=ProjectPortfolioSeeder
php artisan test --filter=Portfolio
php artisan test --filter=Program
php artisan test --filter=ProjectRisk
php artisan test --filter=ProjectIssue
php artisan test --filter=ProjectBaseline
php artisan test --filter=ProjectBudget
php artisan test --filter=Forecast
php artisan test --filter=ExecutiveDashboard
php artisan test --filter=ProjectDependency
```

## Notes
- Portfolios and programs are tenant-scoped; codes are unique per organization.
- Cross-project dependencies reject self-links and cycles (`DependencyGraphService`).
- Risk severity = probability × impact (1–5 each); heatmap excludes `closed` / `accepted`.
- Issue resolve is available via `IssueManagementService::resolve()` or `update` with `status=resolved`.
- Risk escalate is service-level (`RiskManagementService::escalate`); no dedicated HTTP route yet.
- Baselines snapshot scope, schedule, budget, and progress; variance analysis builds flags on top of `BaselineService::compare`.
- Budget defaults seed system categories from `config('projects.default_budget_categories')` (Labor, Materials, Software, Travel, Contingency, Other); variance threshold defaults to 10%.
- Portfolio executive (`portfolios/executive`) uses `ExecutiveDashboardService`; project executive (`projects/executive`) remains Phase 12.4 health rollup.
- Report types: portfolio, program, risk, budget, executive, variance, forecast — formats pdf/excel/csv.
