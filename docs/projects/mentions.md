# Mentions

## Overview
`@username` mentions in comments (and other text bodies) resolve to organization users, persist as `project_mentions`, notify the mentioned user, and feed the Collaboration Center and Mentions inbox.

## Database Tables
| Table | Model | Purpose |
| --- | --- | --- |
| `project_mentions` | `ProjectMention` | Mention history (project/task, source morph, excerpt, `read_at`) |

Key fields: `mentioned_user_id`, `mentioned_by`, `source_type` / `source_id`, `excerpt`, optional `project_id` / `task_id`.

## Services
`MentionService`:
- `extractMentions($body)` — parse `@handle` tokens
- `resolveUsers($organizationId, $usernames)` — match by email local-part or name variants
- `recordMentions(...)` — create rows, dispatch `CommentMentioned`, notify users (skips self)
- `highlightMentions($body)` — HTML highlight for display
- `historyForUser` / `historyForProject` — inbox and project feeds

## RBAC Permissions
| Slug | Capability |
| --- | --- |
| `projects.mentions.view` | View mention history / inbox / autocomplete |

Recording mentions is typically a side effect of commenting (task comment permissions), not a separate create permission.

## Workflow Events
| Trigger | Event |
| --- | --- |
| `comment.mentioned` | `CommentMentioned` |

## API / Routes Summary
| Surface | Routes |
| --- | --- |
| Web | `GET mentions` (`mentions.index`); `GET mentions/autocomplete` |
| API | `GET api/mentions`; `GET api/mentions/autocomplete` |

## UI
- Mentions inbox: `resources/views/projects/mentions/index.blade.php`
- Collaboration Center surfaces project mentions in the aggregated feed
- Global search (`projects.mentions.view`) matches `excerpt`; links to `projects.collaboration.show` when a project is set, otherwise `mentions.index`

## Acceptance Notes
- Self-mentions are ignored.
- Excerpt is truncated (~240 chars) from the source body.
- Resolution is org-membership aware; unknown handles still render but are not stored.
- Models use `Auditable` + `BelongsToOrganization`.
