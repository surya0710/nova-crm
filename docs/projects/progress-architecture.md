# Progress Architecture

## Purpose
Technical map of Phase 12.4 Progress Tracking & Reporting and its integrations with Projects, Tasks, Resources, and the platform stack.

## Layers
```
Controller → Form Request → Service → Models
Dashboard Widget → Widget Provider → Health/Statistics Services
API Controller → Policy → Service → API Resource
Reporting Service → Statistics / Timeline / MilestoneProgress → Storage export
```

## Database Tables
| Table | Purpose |
| --- | --- |
| `progress_updates` | Manual progress history |
| `project_health_snapshots` | Point-in-time health calculations |
| `project_reports` | Generated report metadata and storage paths |

All include `organization_id`; models use `BelongsToOrganization` and organization scope.

## Services
| Service | Responsibility |
| --- | --- |
| `ProgressTrackingService` | CRUD progress updates, notifications, `ProgressUpdated` |
| `ProjectHealthService` | Weighted completion, status rules, snapshots, portfolio summary |
| `ProjectStatisticsService` | Task open/closed/overdue, velocity, team productivity |
| `MilestoneProgressService` | Planned vs actual milestone progress, delay detection |
| `TimelineService` | Timeline graph + Gantt serialization |
| `ProjectReportingService` | Payload assembly, PDF/Excel/CSV export, `ProjectReport` persistence |

## Health calculation flow
```
Tasks (avg %) ──┐
Milestones (%) ─┼→ weighted completion → thresholds → health_status → snapshot
Manual (latest) ┘
Overdue tasks / delayed milestones / schedule variance → threshold checks
```

Config: `config/projects.php` → `completion_weights`, `health_thresholds`, `health_statuses`.

## Integrations
| Module | Integration |
| --- | --- |
| Tasks | Task completion %, overdue detection, timeline bars, time tracking reports |
| Milestones | Manual link on updates; milestone % in health; milestone progress API |
| Resources | Allocation rows in timeline and utilization reports |
| RBAC | `projects.progress.*`, `projects.health.view`, `projects.reports.*`, timeline/gantt/statistics |
| Metadata | Entity `project_progress_update` on `ProgressUpdate.metadata` |
| Search | Progress updates and reports indexed when permitted |
| Audit | `ProgressUpdate` auditable |
| Workflows | Domain events on progress, health, reports, timeline |

## Multi-Tenancy
Services and controllers rely on `TenantContext` and route model binding scoped by organization. Cross-tenant IDs return 404.

## UI surfaces
- Progress list + Progress Center (per project)
- Health detail + recalculate
- Reports list + download
- Gantt chart
- Executive portfolio dashboard
- Dashboard widgets: health summary, at-risk list, recently updated projects

## Extension Points
- Adjust weights/thresholds in config
- New report types in `ProjectReportingService::buildPayload()`
- Workflow listeners on progress/health events
- Additional dashboard widget providers in `config/dashboard.php`

## Related Documentation
See [architecture](architecture.md), [progress-developer-guide](progress-developer-guide.md), and [progress-api](progress-api.md).
