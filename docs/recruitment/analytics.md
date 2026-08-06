# Recruitment Analytics

## Purpose
Transform recruitment operational data into organization-scoped KPIs, funnel intelligence, source effectiveness, recruiter performance, and candidate/job analytics.

## Ownership
Recruitment Analytics owns dashboards, KPI calculation, funnel analytics, recruiter/hiring-manager performance, source effectiveness, time analytics, and trend reporting. Analytics **never** modifies recruitment business records.

## Architecture
```
Controller → Form Request → Analytics Service → Aggregation Queries → Views
```

## Services
- `RecruitmentKpiService` — executive KPIs and time metrics
- `RecruitmentDashboardService` — executive and hiring-manager dashboard payloads
- `RecruitmentAnalyticsService` — funnel, sources, recruiters, candidates, openings, departments
- `RecruitmentTrendService` — hiring, candidate, offer, volume, and source trends
- `RecruitmentReportService` — named report compilation and saved reports
- `RecruitmentExportService` — CSV/Excel exports (PDF placeholder)
- `RecruitmentAnalyticsCache` — short-lived cached aggregations with version bump on data change

## Key Metrics
- Open positions, active candidates, interviews scheduled, offers pending/accepted
- Hiring rate, time to hire, time to fill, offer acceptance rate
- Funnel conversion and drop-off by stage
- Source applications → interviews → offers → hires
- Recruiter leaderboards (daily/weekly/monthly/quarterly/yearly)
- Department hiring and vacancy aging

## Permissions
- `recruitment.analytics.view`
- `recruitment.reports.view`
- `recruitment.reports.export`
- `recruitment.reports.manage`

## Multi-Tenancy
All queries rely on `BelongsToOrganization` scopes. Managers without `recruitment.manage` are restricted to authorized departments.

## Caching
KPI results are cached per organization with a version stamp. Creating/updating/deleting recruitment records bumps the version via `RecruitmentAnalyticsCacheObserver`.

## Out of Scope
AI predictions, scheduled email reports, Power BI/Tableau/Looker, cross-module executive dashboards, and cross-organization benchmarking.
