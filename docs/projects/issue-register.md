# Issue Register

## Overview
Organization-scoped issue register for problems affecting projects, portfolios, or programs. Issues track priority, severity, owner, resolution, and root cause. Resolving sets `resolved_at` and fires a dedicated event.

## Database Tables
| Table | Model | Purpose |
| --- | --- | --- |
| `project_issues` | `ProjectIssue` | Issue records (title, priority, severity, status, resolution, root cause) |

## Services
`IssueManagementService`:
- `create` / `update` / `delete` — CRUD; `update` with `status=resolved` sets `resolved_at` and fires resolve event
- `resolve($issue, $actor, ?$resolution)` — explicit resolve path
- `list` — filters: `project_id`, `portfolio_id`, `program_id`, `status`, `priority`

## RBAC Permissions
| Slug | Capability |
| --- | --- |
| `projects.issues.view` | List/view issues |
| `projects.issues.create` | Create issues |
| `projects.issues.update` | Edit / resolve issues |
| `projects.issues.delete` | Delete issues |
| `projects.issues.manage` | Full issue administration |

Project helpers: `viewIssues`, `createIssues`, `updateIssues`.

## Workflow Events
| Trigger | Event |
| --- | --- |
| `project.issue.created` | `ProjectIssueCreated` |
| `project.issue.resolved` | `ProjectIssueResolved` |

Hard delete does not emit a domain event.

## API / Routes Summary
| Surface | Routes |
| --- | --- |
| Web | Org `issues` index/store/update/destroy; nested `projects/{project}/issues` |
| API | `api/v1/issues` and nested project issues |

## UI
Blade under `resources/views/issues/` and `resources/views/projects/issues/`. Global search matches issues with `projects.issues.view`.

## Acceptance Notes
- Issues are tenant-scoped; cross-org IDs return 404.
- Controllers resolve via `update` with resolved status; `resolve()` is available for service/API callers.
- Audit via `Auditable` on `ProjectIssue`.
