# Collaboration Center

## Overview
Per-project hub that aggregates discussion (task comments), progress updates, mentions, audit activity, watchers, pinned items, and shared links into a single feed. Pins highlight important comments or updates for the team.

## Database Tables
| Table | Model | Purpose |
| --- | --- | --- |
| `project_collaboration_pins` | `ProjectCollaborationPin` | Pinned feed items (`source_type` / `source_id`, title, body, sort) |
| (related) `task_comments`, `progress_updates`, `project_mentions`, `project_watchers`, `audit_logs` | — | Feed sources |

Unique pin key: `(project_id, source_type, source_id)`.

## Services
`CollaborationService`:
- `feed($project, $options)` — merged timeline (`comments`, `progress_updates`, `mentions`, `activity`, `watchers`, `pins`, `shared_links`, `items`)
- `pin` / `unpin` / `unpinById` — manage pins; dispatches `ProjectCollaborationUpdated`

Task comments remain owned by the Tasks module; collaboration only aggregates them.

## RBAC Permissions
| Slug | Capability |
| --- | --- |
| `projects.collaboration.view` | Open Collaboration Center / read feed |
| `projects.collaboration.manage` | Pin and unpin items |

## Workflow Events
| Trigger | Event |
| --- | --- |
| `project.collaboration.updated` | `ProjectCollaborationUpdated` |

## API / Routes Summary
| Surface | Routes |
| --- | --- |
| Web | `GET projects/{project}/collaboration`; `POST .../pins`; `DELETE .../pins/{pin}` |
| API | Same under `/api` |

## UI
- Center: `resources/views/projects/collaboration/show.blade.php`
- Linked from project navigation
- Global search: discussion comments (`projects.collaboration.view` / `.manage`) and mentions with a project link to this center

## Acceptance Notes
- Feed limit defaults to 50 per source type before merge/sort.
- Archived projects remain readable; pin mutations should respect project write policies.
- Pins cascade with project delete; audit activity is org-scoped to project and its tasks.
