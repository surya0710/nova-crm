# P12 Phase 12.4 — Progress Tracking & Reporting Progress

## Phase
Phase 12.4 — Project Progress Tracking & Reporting

## Outcome
Organization-scoped progress tracking, automated project health, statistics, timeline/Gantt data, downloadable reports (PDF/Excel/CSV), executive portfolio views, workflow events, RBAC, dashboard widgets, REST APIs, Blade UI, documentation, and tests.

## Delivered

| Area | Status |
| --- | --- |
| Tables (`progress_updates`, `project_health_snapshots`, `project_reports`) | Done |
| Models, services (progress, health, statistics, timeline, reporting, milestone progress) | Done |
| Weighted completion + health thresholds (on_track / at_risk / delayed / completed / archived) | Done |
| Domain events + workflow triggers (`project.progress.*`, `project.health.*`, etc.) | Done |
| RBAC permissions (`projects.progress.*`, `projects.health.view`, `projects.reports.*`, timeline/gantt/statistics) | Done |
| Metadata entity `project_progress_update` | Done |
| Dashboard widgets + quick actions (health, at-risk, recently updated, executive) | Done |
| Web + API controllers/routes | Done |
| Blade UI (progress, Progress Center, health, reports, Gantt, executive) | Done |
| Seeders (`ProjectProgressTrackingSeeder` and related) | Done |
| Documentation (`docs/projects/progress-*.md`, health, reporting, Gantt, executive) | Done |
| Unit/feature tests (`ProjectHealth*`, `Progress*`, `ProjectReporting*`, `Timeline*`, `ProjectStatistics*`) | Done |

## Run

```bash
php artisan migrate
php artisan db:seed --class=ProjectProgressTrackingSeeder
php artisan test tests/Unit/ProjectHealthServiceTest.php tests/Unit/ProgressTrackingServiceTest.php tests/Unit/ProjectReportingServiceTest.php tests/Unit/TimelineServiceTest.php tests/Unit/ProjectStatisticsServiceTest.php tests/Feature/ProjectHealthTest.php tests/Feature/ProjectProgressUpdateTest.php tests/Feature/ProjectReportingTest.php tests/Feature/ProjectTimelineGanttTest.php tests/Feature/ProjectMilestoneProgressTest.php tests/Feature/ProjectStatisticsTest.php tests/Feature/ProjectProgressDashboardTest.php tests/Feature/ProjectProgressNotificationTest.php tests/Feature/ProjectProgressWorkflowEventTest.php tests/Feature/ProjectProgressAuditTest.php tests/Feature/ProjectProgressSearchTest.php tests/Feature/ProjectProgressRbacTest.php
```

## Notes
- Progress updates are append-only on create; project `completion_percentage` reflects the latest posted percentage.
- Health uses configurable weights (task 50%, milestone 30%, manual 20%) and threshold tables in `config/projects.php`.
- Reports export to `storage/app/project-reports/{organization_id}/`; `ProjectReport.storage_path` stores the relative path.
- Recalculate health via API/web `?recalculate=1` or first access when no snapshot exists.
- Executive dashboard aggregates latest snapshot per project via `ProjectHealthService::portfolioSummary()`.
