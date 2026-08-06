# Notification APIs

Notification access for mobile clients.

---

## API Availability

| Feature | Endpoint | Status |
|---------|----------|--------|
| Notification list | `GET /api/v1/notifications` | **Not implemented** |
| Mark read | `POST /api/v1/notifications/{id}/read` | **Not implemented** |
| Mark all read | `POST /api/v1/notifications/read-all` | **Not implemented** |
| Unread count | — | **Partial** via widgets |
| Push device registration | — | **Not implemented** |
| Notification preferences | `GET/PUT /api/v1/notification-preferences` | **Available** (projects scope) |
| Dashboard widget | `GET /api/v1/dashboard/widgets/notifications/data` | **Available** |

---

## Dashboard Widget — Recent Notifications

`GET /api/v1/dashboard/widgets/notifications/data`

| Requirement | Value |
|-------------|-------|
| Permission | None (widget-level) |
| Module license | `notifications` |
| Dashboard permission | `dashboard.view` |

### Response

```json
{
  "notifications": [
    {
      "id": "9b2c3d4e-...",
      "title": "Leave Approved",
      "message": "Your annual leave request has been approved.",
      "action_url": "/hrms/ess/leave",
      "read_at": null,
      "created_at": "2026-07-21T10:30:00+00:00"
    }
  ],
  "unread_count": 3
}
```

Default limit: 5 notifications.

### Notification Data Fields

Stored in Laravel `notifications` table `data` JSON:

| Field | Description |
|-------|-------------|
| `organization_id` | Tenant scope |
| `title` | Display title |
| `message` / `body` | Content |
| `action_url` / `url` | Deep link target |
| `category` | e.g. `general`, `leave`, `attendance` |
| `priority` | `normal`, `high`, etc. |
| `workspace` | Source workspace |

---

## Web Session Endpoint (Not Sanctum)

`GET /shell/notifications`

**Controller:** `NotificationDrawerController`  
**Auth:** Web session (not Bearer token)

```json
{
  "notifications": [
    {
      "id": "uuid",
      "title": "Notification",
      "body": "Message body",
      "url": "/notifications",
      "category": "general",
      "priority": "normal",
      "workspace": "hrms",
      "read": false,
      "created_at": "2 hours ago"
    }
  ],
  "unread": 3
}
```

Not usable by mobile Sanctum clients without session cookies.

---

## Web Notification Pages

| Route | Action |
|-------|--------|
| `GET /notifications` | Paginated inbox (HTML) |
| `POST /notifications/{id}/read` | Mark single read, redirect to action URL |
| `POST /notifications/read-all` | Mark all unread in current org |

---

## Notification Preferences API

`GET /api/v1/notification-preferences`  
`PUT /api/v1/notification-preferences`

| Requirement | Value |
|-------------|-------|
| Permission | `projects.notifications.manage` |
| Scope | **Project notifications only** |

Not HRMS/attendance/leave notification preferences.

### Response Fields (`NotificationPreferenceResource`)

| Field | Type |
|-------|------|
| `in_app_enabled` | boolean |
| `email_enabled` | boolean |
| `digest_enabled` | boolean |
| `digest_frequency` | string |
| `muted_projects` | array |
| `muted_tasks` | array |
| `event_preferences` | object |
| `channels` | object |

---

## Push Notifications

**Not implemented.**

No endpoints, models, or services found for:

- FCM token registration
- APNs device tokens
- Push notification dispatch

---

## Recommended Future API

```
GET  /api/v1/notifications?page=1&per_page=20
GET  /api/v1/notifications/unread-count
POST /api/v1/notifications/{id}/read
POST /api/v1/notifications/read-all
POST /api/v1/devices/register
DELETE /api/v1/devices/{token}
```

### Device Registration (Future)

```json
{
  "platform": "android",
  "token": "fcm-device-token",
  "device_name": "Pixel 8"
}
```

### Push Payload Structure (Proposed)

Mirror notification `data` fields:

```json
{
  "notification": {
    "title": "Leave Approved",
    "body": "Your request was approved."
  },
  "data": {
    "organization_id": "42",
    "category": "leave",
    "action_url": "/hrms/ess/leave",
    "notification_id": "uuid"
  }
}
```

---

## HR Notification Config (Web Admin)

`GET/PUT /organization/hr-config/notifications` — org-level HR notification settings, web only.

---

## Mobile Screen Mapping

```
Notifications Screen
  ↓
GET /dashboard/widgets/notifications/data  (limited — 5 items)
  ↓
[Full inbox — NOT AVAILABLE via Sanctum API]
```

Poll widget on app resume until dedicated API exists.

---

## Permissions

| Permission | Purpose |
|------------|---------|
| `dashboard.view` | Access notification widget |
| `projects.notifications.manage` | Project notification preferences |

No specific permission for reading own notifications (widget has `permission_slug: null`).
