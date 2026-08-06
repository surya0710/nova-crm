# Projects Architecture

## Purpose
Technical map of the Project Foundation module and its integrations.

## Layers
```
Controller → Form Request → Project Service → Models
Controller → Catalog Service → Project Category/Type/Status/Lifecycle Models
Dashboard → Widget Provider → Tenant-Scoped Queries
```

## Multi-Tenancy
All project tables include `organization_id`. Models use `BelongsToOrganization` and `OrganizationScope`. Services set `TenantContext` before queries. Cross-tenant IDs return 404.

## Database Tables
| Table | Purpose |
| --- | --- |
| `project_categories` | Delivery category catalog |
| `project_types` | Billing/delivery type catalog |
| `project_statuses` | Operational status catalog |
| `project_lifecycle_stages` | Delivery lifecycle stages |
| `projects` | Core project record |
| `project_members` | Team membership and project roles |
| `project_milestones` | Planned delivery checkpoints |
| `project_labels` / `task_labels` | Org label catalog and task attachments |
| `project_watchers` / `task_watchers` | Opt-in watch subscriptions |
| `task_recurrences` | Recurring task schedules |
| `project_templates` (+ template milestones/tasks/checklists/labels) | Project blueprints |
| `project_mentions` | `@mention` history |
| `notification_preferences` | Watcher/mention channel and mute prefs |
| `project_calendar_links` | Internal (and future external) calendar events |
| `project_collaboration_pins` | Collaboration Center pins |
| `portfolios` / `portfolio_projects` | Portfolio catalog and project membership |
| `programs` / `program_projects` | Program catalog and project membership |
| `project_dependencies` | Cross-project dependency edges |
| `project_risks` / `project_issues` | Risk and issue registers |
| `project_baselines` | Versioned project baselines |
| `budget_categories` / `project_budgets` / `budget_items` | Budget categories and project budgets |
| `portfolio_reports` | Generated portfolio/EPM reports |

## Services
| Service | Responsibility |
| --- | --- |
| `ProjectDefaultsService` | Seed system catalogs per organization |
| `ProjectService` | CRUD, archive/restore, numbering, metadata |
| `ProjectCategoryService` | Category catalog management |
| `ProjectTypeService` | Type catalog management |
| `ProjectStatusService` | Status catalog management |
| `ProjectLifecycleService` | Lifecycle stage management and transitions |
| `ProjectMemberService` | Membership assignment and removal |
| `ProjectMilestoneService` | Milestone CRUD and completion |
| `ProjectLabelService` | Label catalog and task attach/detach |
| `WatcherService` | Project/task watch subscriptions and notify fan-out |
| `MentionService` | Parse, resolve, record, and highlight mentions |
| `TaskRecurrenceService` / `TaskGenerationService` | Recurrence rules and occurrence generation |
| `ProjectTemplateService` / `TemplateCloneService` | Template CRUD and create-project-from-template |
| `CalendarSyncService` | Upsert project/milestone/task calendar links |
| `CollaborationService` | Collaboration Center feed and pins |
| `ProjectAutomationService` | Workflow helper actions (next task, escalations, notifies) |
| `NotificationPreferenceService` | Per-user mute/channel/event preferences |
| `PortfolioService` | Portfolio CRUD, archive, project attach/detach |
| `ProgramService` | Program CRUD and project membership |
| `DependencyGraphService` | Cross-project dependencies, graph, impact analysis |
| `RiskManagementService` | Risk CRUD, escalate, heatmap matrix |
| `IssueManagementService` | Issue CRUD and resolve |
| `BaselineService` | Baseline capture and raw compare |
| `VarianceAnalysisService` | Enhanced baseline variance flags |
| `BudgetService` | Budget categories, budgets, line items, totals |
| `ForecastService` | Project/portfolio forecasts and capacity |
| `PortfolioStatisticsService` | Portfolio rollup analytics |
| `ExecutiveDashboardService` | Org-wide portfolio executive payload |
| `PortfolioReportingService` | Portfolio/EPM report generation |

## RBAC
Organization permissions (`projects.view`, `projects.create`, `projects.edit`, `projects.manage`) gate module access. Project-level roles (`owner`, `manager`, `team_member`, etc.) are stored on `project_members` and defined in `config/projects.php`.

## Metadata
Projects persist custom field values in JSON `metadata` and synchronize projections through `MetadataEntityFormService` using entity key `project`.

## Events
| Event | Trigger |
| --- | --- |
| `ProjectCreated` | Project created |
| `ProjectUpdated` | Project fields or metadata changed |
| `ProjectArchived` | Project archived |
| `ProjectRestored` | Archived project restored |
| `ProjectLifecycleChanged` | Lifecycle stage changed |
| `ProjectMemberAssigned` | Member added or role updated |
| `ProjectMemberRemoved` | Member deactivated or removed |
| `ProjectMilestoneCreated` | Milestone created |
| `ProjectMilestoneCompleted` | Milestone marked complete |

## Phase 12.5 Collaboration
Collaboration extends Projects with labels, watchers, mentions, templates, recurrence, calendar links, Collaboration Center pins, and automation helpers. Permissions live under `projects.labels.*`, `projects.watchers.*`, `projects.mentions.view`, `projects.templates.*`, `projects.recurrence.*`, `projects.collaboration.*`, `projects.automation.*`, `projects.calendar.*`, and `projects.notifications.manage`. Feature docs: [project-labels](project-labels.md), [watchers](watchers.md), [mentions](mentions.md), [recurring-tasks](recurring-tasks.md), [project-templates](project-templates.md), [calendar-sync](calendar-sync.md), [collaboration-center](collaboration-center.md), [project-automation](project-automation.md). Phase overview: [P12_PHASE_12_5_COLLABORATION](../P12_PHASE_12_5_COLLABORATION.md).

## Phase 12.6 Portfolio / EPM
Portfolio management adds portfolios, programs, cross-project dependencies, risk/issue registers, baselines/variance, budgeting, forecasting, portfolio statistics/reporting, and the portfolio executive dashboard. Permissions live under `projects.portfolios.*`, `projects.programs.*`, `projects.dependencies.*`, `projects.risks.*`, `projects.issues.*`, `projects.baselines.*`, `projects.budgets.*`, `projects.forecasts.view`, `projects.executive.view`, and `projects.portfolio_reports.*`. Feature docs: [portfolio-management](portfolio-management.md), [program-management](program-management.md), [project-dependencies](project-dependencies.md), [risk-register](risk-register.md), [issue-register](issue-register.md), [project-baselines](project-baselines.md), [project-budgeting](project-budgeting.md), [portfolio-analytics](portfolio-analytics.md), [forecasting](forecasting.md), [executive-dashboard](executive-dashboard.md). Phase overview: [P12_PHASE_12_6_PORTFOLIO](../P12_PHASE_12_6_PORTFOLIO.md).

## Dashboard Widgets
Widget providers under `App\Services\Dashboard\Widgets` extend `AbstractWidgetProvider` and register via `config/dashboard.php` (managed by platform config).

## Audit
Project models use the `Auditable` concern for change tracking.

## Extension Points
- Workflow listeners on project events
- Metadata field definitions for entity `project`
- Additional widget providers registered in dashboard config
