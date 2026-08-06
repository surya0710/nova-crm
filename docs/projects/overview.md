# Projects Overview

## Purpose
Introduces the Project Foundation module for planning, tracking, and delivering client and internal work across the organization.

## Core Areas
- Project records with owner, manager, client, and department context
- Organization-scoped catalogs: categories, types, statuses, and lifecycle stages
- Team membership with project-level roles
- Milestones and linked tasks
- Metadata-driven custom fields (entity key `project`)
- Dashboard widgets for personal and operational visibility
- Collaboration (Phase 12.5): labels, watchers, mentions, templates, recurring tasks, calendar sync, Collaboration Center
- Portfolio / EPM (Phase 12.6): portfolios, programs, dependencies, risks, issues, baselines, budgets, forecasts, executive dashboard

## Platform Ownership
Projects owns project delivery data, membership, milestones, and lifecycle configuration. CRM owns customer records linked as clients. Tasks module owns task execution linked polymorphically to projects. RBAC owns organization permissions; project roles govern in-project responsibilities.

## Foundation Scope (Phase 12.1)
Phase 12.1 delivers schema, models, services, defaults seeding, permissions sync, dashboard widgets, and documentation. UI routes and controllers may follow in subsequent phases.

## Collaboration Scope (Phase 12.5)
Phase 12.5 adds labels, watchers, mentions, project templates, recurring tasks, calendar sync, Collaboration Center, and automation helpers. See [P12_PHASE_12_5_COLLABORATION](../P12_PHASE_12_5_COLLABORATION.md) and the feature guides linked from there.

## Portfolio Scope (Phase 12.6)
Phase 12.6 adds enterprise portfolio management: portfolios and programs, cross-project dependencies, risk/issue registers, baselines and variance, budgeting, forecasting, portfolio analytics/reporting, and the portfolio executive dashboard. See [P12_PHASE_12_6_PORTFOLIO](../P12_PHASE_12_6_PORTFOLIO.md) and the feature guides linked from there.

## Related Documentation
See [architecture](architecture.md), [lifecycle](lifecycle.md), [roles](roles.md), [metadata-integration](metadata-integration.md), [apis](apis.md), [user-guide](user-guide.md), [administrator-guide](administrator-guide.md), [developer-guide](developer-guide.md), [project-labels](project-labels.md), [watchers](watchers.md), [mentions](mentions.md), [recurring-tasks](recurring-tasks.md), [project-templates](project-templates.md), [calendar-sync](calendar-sync.md), [collaboration-center](collaboration-center.md), [project-automation](project-automation.md), [portfolio-management](portfolio-management.md), [program-management](program-management.md), [project-dependencies](project-dependencies.md), [risk-register](risk-register.md), [issue-register](issue-register.md), [project-baselines](project-baselines.md), [project-budgeting](project-budgeting.md), [portfolio-analytics](portfolio-analytics.md), [forecasting](forecasting.md), and [executive-dashboard](executive-dashboard.md).
