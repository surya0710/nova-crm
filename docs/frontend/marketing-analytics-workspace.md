# Marketing & Analytics Workspace

Phase **14.8** delivers the Konnect Nex **Marketing** and **Analytics / Business Intelligence** workspaces. Both run inside the tenant AppShell (Blade + Alpine + Vite) and reuse existing marketing platform, reporting, executive dashboard, and forecast services.

Use with [crm-reference-implementation.md](./crm-reference-implementation.md), [projects-workspace.md](./projects-workspace.md), [hrms-workspace.md](./hrms-workspace.md), and [organization-administration.md](./organization-administration.md) for shared Enterprise UX patterns.

---

## Entry

| Workspace | Base URL | Home route | Nav |
|-----------|----------|------------|-----|
| Marketing | `/marketing` | `marketing.home` | `config/navigation.php` → `workspaces.marketing` + `menus.marketing` |
| Analytics | `/analytics` | `analytics.home` | `workspaces.analytics` + `menus.analytics` |

**Auth:** Tenant `web` guard + `set.organization`. Organization-scoped queries only.

---

## Marketing workspace

### Home

| Piece | Path |
|-------|------|
| Controller | `App\Http\Controllers\Marketing\MarketingHomeController` |
| Aggregator | `App\Services\Marketing\MarketingWorkspaceHomeService` |
| View | `resources/views/marketing/home.blade.php` |
| Layout | `x-layouts.workspace-home` |

**Widgets:** Active Campaigns · Leads Generated · Cost Per Lead · Conversion Rate · Campaign ROI · Channel Performance · Google Ads Summary · Meta Ads Summary · Email Campaign Performance · Landing Page Performance · Attribution Overview · Recent Campaign Activity · Quick Actions.

**Personalization:** Alpine Customize panel PATCHes `workspace.dashboard-preferences.update` (`workspace=marketing`). Hidden widgets stored in `UserUiPreference.dashboard_layout.marketing`.

**Permission gate:** any of `marketing.view`, `marketing.manage`, `integrations.view`, `integrations.manage`.

### Campaigns

| Area | Routes | Notes |
|------|--------|-------|
| List | `marketing.campaigns.index` | Status filter |
| Create / Edit | `marketing.campaigns.create|store|edit|update` | Budget, channels, audience, UTM, timeline |
| Detail | `marketing.campaigns.show` | Performance from touches/attributions/conversions |
| Delete | `marketing.campaigns.destroy` | Manage permission |

Model: `MarketingCampaign` (additive `marketing_campaigns` table). Service: `MarketingCampaignService`. UTM campaign key joins existing `marketing_touches.campaign`.

### Attribution & providers

| Area | Route | Backend |
|------|-------|---------|
| Attribution | `marketing.attribution.index` | `MarketingAttribution`, touches, conversions |
| Providers hub | `marketing.providers.index` | `MarketingProviderService::integrationCardsForOrganization` + health |
| OAuth / credentials | existing `marketing.providers.*`, `integrations.*` | Unchanged |

Planned (non-connectable) cards: Google Analytics, GTM, Email, SMS.

---

## Analytics workspace

### Home

| Piece | Path |
|-------|------|
| Controller | `App\Http\Controllers\Analytics\AnalyticsHomeController` |
| Aggregator | `App\Services\Analytics\AnalyticsWorkspaceHomeService` |
| View | `resources/views/analytics/home.blade.php` |

**KPIs / cards:** Pipeline vs target · Project health · Headcount · Outstanding AR · Sales · Delivery · People · Finance · Audit · Attention · Quick Actions.

**Personalization:** same preference endpoint with `workspace=analytics`.

**Permission gate:** any of `reports.view`, `finance.view`, `audit.view`, `projects.reports.view`, `recruitment.reports.view`.

### Domain pages

| Page | Route | Service method |
|------|-------|----------------|
| Executive | `analytics.executive` | `AnalyticsDomainService::executive` |
| CRM | `analytics.crm` | `::crm` |
| Projects | `analytics.projects` | `::projects` |
| HR | `analytics.hr` | `::hr` |
| AI Insights | `analytics.ai-insights` | `AnalyticsInsightsService::build` |
| Dashboards | `analytics.dashboards.index` | Template gallery |
| KPI Library | `analytics.kpis.index` | `KpiLibraryService::catalog` |
| Reports Center | `analytics.reports.index` | `ReportService::compile` + deep links |

AI insights are **rule-based** (ForecastService, pipeline, leave, follow-ups). Every insight includes `requires_review => true`. No external LLM calls.

KPI definitions live in `config/analytics_kpis.php` (CRM, Projects, HRMS, Marketing, Finance, Recruitment) with configurable thresholds.

---

## Search & command palette

| Provider | Workspace group |
|----------|-----------------|
| `MarketingCommandProvider` | Marketing |
| `AnalyticsCommandProvider` | Analytics |
| `MarketingCampaignSearchProvider` | marketing |
| `MarketingProviderSearchProvider` | marketing |
| `AnalyticsViewSearchProvider` | analytics |
| `AnalyticsKpiSearchProvider` | analytics |

Registered in `AppServiceProvider`.

---

## Empty states

Presets on `x-ui.empty-state-preset`: `campaigns`, `attribution`, `analytics`, `dashboards`, `kpis`, `ai_insights` (plus existing `reports`, `providers`, `integrations`).

---

## Responsive & accessibility

Homes and domain pages use shared Enterprise UX layouts (`workspace-home`, `analytics`, `entity-listing`, `entity-detail`, `create`/`edit`) with responsive grids (`sm` / `md` / `xl`), breadcrumbs, and labeled form controls for WCAG AA alignment with the rest of Phase 14.
