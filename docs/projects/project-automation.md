# Project Automation

## Overview
Focused helpers for workflow-driven project automation. Business logic lives in `ProjectAutomationService` (not inside workflow JSON), so listeners and workflow actions call typed methods for follow-up tasks, escalations, and stakeholder notifications.

## Database Tables
Phase 12.5 does not introduce a separate automation-rules table. Automation reuses existing project/task/milestone rows and the platform workflow engine (`config/workflows.php` triggers + actions).

## Services
`ProjectAutomationService`:
- `createNextTaskOnCompletion($completedTask, $nextTaskData, $actor)` — spawn follow-up task under the same project
- `notifyManagerOnMilestoneComplete($milestone, $actor?)` — notify manager + watchers
- `escalateOverdueTask($task, $actor?)` — notify assignees/stakeholders for overdue work
- `notifyPmOnProjectDelayed($project, $actor?, $reason?)` — PM / watcher alert on delay

Related: `WatcherService::notifyWatchers` for fan-out; workflow triggers from progress/health/task events drive these helpers.

## RBAC Permissions
| Slug | Capability |
| --- | --- |
| `projects.automation.view` | View automation overview / status |
| `projects.automation.manage` | Configure or invoke managed automation surfaces |

Underlying task/project mutations still require normal `tasks.*` / `projects.*` permissions when performed interactively.

## Workflow Events
Automation **consumes** existing triggers (examples):
- `task.completed` → next-task helpers
- `project.milestone.completed` / milestone events → manager notify
- `project.delayed` / health change → PM notify
- Watcher / mention / collaboration triggers for notification side effects

No exclusive Phase 12.5 trigger is required beyond wiring these helpers into workflow actions.

## API / Routes Summary
| Surface | Routes |
| --- | --- |
| Web | `GET projects/automation` (`projects.automation`) |
| API | `GET api/projects/automation` |

## UI
- Automation overview: `resources/views/projects/automation/index.blade.php`
- Documents available helpers and how they connect to workflow definitions

## Acceptance Notes
- Follow-up task creation no-ops if the completed task is not closed/completed or has no project.
- Archived projects reject next-task creation.
- Prefer calling the service from workflow actions rather than duplicating notification logic in controllers.
- Keep helpers side-effect free beyond their documented notifications/task creates.
