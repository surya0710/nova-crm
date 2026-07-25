# Phase 14.8 Progress — Marketing, Analytics & Business Intelligence Workspace

**Status:** Complete  
**Date:** 2026-07-25  
**Scope:** Deliver Marketing and Analytics workspaces as the unified intelligence layer on shared Enterprise UX (Blade + Alpine + Vite). Reuses P7 marketing platform services, ReportService, ExecutiveDashboardService, ForecastService, and Dashboard preferences.

---

## Outcome

NovaCRM exposes actionable insights across CRM, Projects, HRMS, Recruitment, Finance, and Marketing through Marketing and Analytics workspace homes, campaign management, provider health, cross-module analytics, AI-assisted insights (human review required), KPI library, reports center, search, and command palette.

---

## Deliverables

| # | Deliverable | Status | Notes |
|---|-------------|--------|-------|
| 1 | Marketing Workspace Home | Done | `marketing.home` — campaigns, leads, CPL, conversion, ROI, channels, ads summaries, landing pages, attribution, activity, quick actions; widget personalization |
| 2 | Campaign Management | Done | List / detail / create / edit; budget, channels, performance, audience, attribution, timeline; `marketing_campaigns` additive table |
| 3 | Marketing Providers | Done | Hub with health indicators; Meta/Google Ads + catalog; GA/GTM/Email/SMS planned cards; credentials remain on Integrations |
| 4 | Executive Analytics | Done | `analytics.executive` — revenue, pipeline, funnel, growth, project health, recruitment, finance KPIs with drill-downs |
| 5 | CRM Analytics | Done | `analytics.crm` — sources, conversion, sales, acquisition, forecast, win/loss |
| 6 | Project Analytics | Done | `analytics.projects` — progress, utilization, budget, milestones, portfolio, delivery |
| 7 | HR Analytics | Done | `analytics.hr` — headcount, attendance, leave, recruitment, performance, payroll, attrition, capacity |
| 8 | AI Insights | Done | `analytics.ai-insights` — rule-based insights from ForecastService + CRM/HR data; `requires_review` on every item |
| 9 | Custom Dashboards | Done | `analytics.dashboards.index` — template gallery + personal dashboard link; role-gated |
| 10 | KPI Library | Done | `config/analytics_kpis.php` + `analytics.kpis.index` with thresholds |
| 11 | Reports Center | Done | `analytics.reports.index` wraps ReportService + deep links |
| 12 | Search Integration | Done | Campaigns, providers, analytics views, KPIs |
| 13 | Command Palette | Done | Marketing + Analytics providers (workspace-grouped) |
| 14 | Empty States | Done | campaigns, attribution, analytics, dashboards, kpis, ai_insights |
| 15 | Responsive & Accessibility | Done | Shared layouts / landmarks / labeled forms |
| 16 | Documentation | Done | This file + marketing-analytics-workspace.md + catalog/progress |

---

## Architecture

```
/marketing/*          tenant auth, set.organization
/analytics/*          tenant auth, set.organization
x-layouts.* / x-ui.*  Enterprise UX shared components
config/navigation.php marketing + analytics workspaces/menus
config/analytics_kpis.php shared KPI definitions
P7 marketing services attribution / conversions / providers
```

Additive schema only: `marketing_campaigns`. No breaking changes. Analytics remain organization-scoped.

---

## Key paths

### Marketing
- Routes: `marketing.home`, `marketing.campaigns.*`, `marketing.attribution.index`, `marketing.providers.index`
- Services: `MarketingWorkspaceHomeService`, `MarketingCampaignService`, existing `MarketingProviderService`
- Model: `MarketingCampaign`
- Views: `resources/views/marketing/**`

### Analytics
- Routes: `analytics.home`, `analytics.executive|crm|projects|hr|ai-insights`, `analytics.dashboards|kpis|reports.index`
- Services: `AnalyticsWorkspaceHomeService`, `AnalyticsDomainService`, `AnalyticsInsightsService`, `KpiLibraryService`
- Views: `resources/views/analytics/**`

### Personalization
- `WorkspaceDashboardPreferenceController` → `workspace.dashboard-preferences.update`
- Stores widget visibility in `UserUiPreference.dashboard_layout['marketing'|'analytics']`

### Search & palette
- `MarketingCommandProvider`, `AnalyticsCommandProvider`
- `MarketingCampaignSearchProvider`, `MarketingProviderSearchProvider`, `AnalyticsViewSearchProvider`, `AnalyticsKpiSearchProvider`

### Permissions
- `marketing.view`, `marketing.manage` (plus existing `integrations.*` / `reports.view` / domain report permissions)

---

## Verification

```bash
php artisan migrate
php artisan route:list --name=marketing
php artisan route:list --name=analytics
php artisan view:cache
php artisan route:clear
```

Smoke: Marketing home widgets · Customize panel · Campaign CRUD · Attribution · Provider health · Analytics home · Executive/CRM/Projects/HR · AI Insights review banner · KPI Library · Reports Center · ⌘K Marketing/Analytics · Global search campaigns/KPIs/views.

---

## Out of scope (later)

Customer Portal, Mobile Application, Public Website, Marketplace, third-party extension ecosystem, live LLM providers, full email/SMS campaign engines, LinkedIn Ads driver.
