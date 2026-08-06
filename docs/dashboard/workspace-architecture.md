# Workspace Architecture

The workspace is the unified landing experience for authenticated tenant users.

## Composition

`WorkspaceService::build()` returns:

```json
{
  "dashboard": { "sections": [], "widgets": [] },
  "quick_actions": [],
  "notifications": { "items": [], "unread_count": 0 },
  "recent_activities": { "assigned_work": [], "recent_actions": [] }
}
```

## Integration points

- **Notifications** — reuses Laravel database notifications filtered by `organization_id`
- **Recent activities** — audit log (permission gated) + assigned tasks
- **Quick actions** — registry in `config/dashboard.php` with dynamic RBAC filtering

## Personalization

Users with `dashboard.customize` can save layout, hide/restore widgets, and reset to defaults.

Organizations with `dashboard.manage` can enable/disable widgets and reorder quick actions.

## Caching

Workspace responses are cached per user/organization with version-bump invalidation on preference or org config changes.
