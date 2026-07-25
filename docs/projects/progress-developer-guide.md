# Progress Developer Guide

## Purpose
Implementation guide for extending Phase 12.4 Progress Tracking & Reporting.

## Key paths
| Area | Path |
| --- | --- |
| Config | `config/projects.php` (weights, thresholds, report types/formats) |
| Migrations | `2026_07_22_000040_create_project_progress_tracking_tables.php`, `2026_07_22_000041_sync_project_progress_permissions.php` |
| Models | `ProgressUpdate`, `ProjectHealthSnapshot`, `ProjectReport` |
| Services | `ProgressTrackingService`, `ProjectHealthService`, `ProjectReportingService`, `TimelineService`, `ProjectStatisticsService`, `MilestoneProgressService` |
| Events | `ProgressUpdated`, `ProjectHealthChanged`, `ProjectCompleted`, `ProjectDelayed`, `ReportGenerated`, `TimelineUpdated` |
| Policies | `ProjectPolicy` (progress/health/report/timeline/gantt/statistics methods) |
| Widgets | `ProjectHealthWidgetProvider`, `ProjectsAtRiskWidgetProvider`, `RecentlyUpdatedProjectsWidgetProvider` |
| Seeders | `ProjectProgressPermissionSeeder`, `ProjectProgressWidgetSeeder`, `ProjectProgressQuickActionSeeder`, `ProjectProgressTrackingSeeder` |
| Web routes | `routes/web.php` — `projects.progress.*`, `projects.health.*`, `projects.reports.*`, `projects.gantt.*`, `projects.executive` |
| API routes | `routes/api.php` — progress/health/reports/statistics/timeline/gantt/milestones/progress |

## Service extension
- **Progress:** extend `ProgressTrackingService`; always call `assertProjectWritable` for mutations; dispatch `ProgressUpdated` on create only.
- **Health:** extend `ProjectHealthService`; adjust weights/thresholds via config before overriding code; snapshots are append-only.
- **Reports:** add report types in config + `buildPayload()` match arm + export helpers if tabular.
- **Timeline:** extend `TimelineService::build()` / `gantt()`; keep organization scoping on task/dependency queries.

Set `TenantContext` before tenant-scoped queries in jobs, widgets, and CLI.

## RBAC slugs
```
projects.progress.view|create|update|delete
projects.health.view
projects.reports.view|generate
projects.timeline.view
projects.gantt.view
projects.statistics.view
```

Re-run `ProjectProgressPermissionSeeder` after adding slugs.

## Metadata entity
Entity key: **`project_progress_update`**. Persist via `MetadataEntityFormService` on create/update (accept `custom_fields` or `metadata` in requests).

## Events and workflows
| Event | Workflow key |
| --- | --- |
| `ProgressUpdated` | `project.progress.updated` |
| `ProjectHealthChanged` | `project.health.changed` |
| `ProjectCompleted` | `project.completed` |
| `ProjectDelayed` | `project.delayed` |
| `ReportGenerated` | `project.report.generated` |
| `TimelineUpdated` | `project.timeline.updated` |

Use `WorkflowDomainEvent::forModel()` with causation metadata from `WorkflowRuntimeContext`.

## Testing
Unit tests: `tests/Unit/ProjectHealthServiceTest.php`, `ProgressTrackingServiceTest.php`, etc.
Feature tests: API + web flows under `tests/Feature/ProjectProgress*.php`, `ProjectHealthTest.php`, etc.

Use `RefreshDatabase`, `Organization::factory()`, `ProjectService::create()`, `TenantContext::set()`, Sanctum + `X-Organization-Id` for API.

## Related Documentation
See [progress-architecture](progress-architecture.md), [progress-api](progress-api.md), and [progress-tracking](progress-tracking.md).
