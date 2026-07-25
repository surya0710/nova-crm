# Watchers

## Overview
Users can watch projects and tasks to receive in-app notifications for relevant activity. Watching is explicit (opt-in) and respects notification preferences (mutes, channel toggles, event preferences).

## Database Tables
| Table | Model | Purpose |
| --- | --- | --- |
| `project_watchers` | `ProjectWatcher` | User watching a project |
| `task_watchers` | `TaskWatcher` | User watching a task |
| `notification_preferences` | `NotificationPreference` | Per-user mute lists and channel/event prefs |

Unique: `(project_id, user_id)`, `(task_id, user_id)`. Preferences unique per `(organization_id, user_id)`.

## Services
`WatcherService`:
- `watchProject` / `unwatchProject`
- `watchTask` / `unwatchTask`
- `listWatching($user)` — projects and tasks the user watches
- `notifyWatchers($subject, $eventType, $message, ...)` — fan-out with preference checks

`NotificationPreferenceService`:
- `getOrCreate` / `update`
- `isMuted` / `shouldNotify`

## RBAC Permissions
| Slug | Capability |
| --- | --- |
| `projects.watchers.view` | View watching list / watchers |
| `projects.watchers.manage` | Watch and unwatch projects/tasks |
| `projects.notifications.manage` | Edit notification preferences |

## Workflow Events
| Trigger | Event |
| --- | --- |
| `project.watcher.added` | `ProjectWatcherAdded` |
| `project.watcher.removed` | `ProjectWatcherRemoved` |
| `task.watcher.added` | `TaskWatcherAdded` |
| `task.watcher.removed` | `TaskWatcherRemoved` |
| `notification.preference.updated` | `NotificationPreferenceUpdated` |

## API / Routes Summary
| Surface | Routes |
| --- | --- |
| Web | `GET projects/watching`; `POST/DELETE projects/{project}/watch`; `POST/DELETE tasks/{task}/watch` |
| API | Same paths under `/api` (`projects/watching`, project/task watch endpoints) |

## UI
- Watching inbox: `projects.watching` → `resources/views/projects/watching/index.blade.php`
- Watch toggles on project/task detail actions
- Dashboard widget gated by `projects.watchers.view`

## Acceptance Notes
- Re-watching an already-watched subject returns the existing row (idempotent).
- Notifications skip the actor and muted subjects; channel/event prefs apply.
- Watcher rows are organization-scoped and cascade with project/task delete.
