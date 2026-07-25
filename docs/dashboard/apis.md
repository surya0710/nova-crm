# Dashboard APIs

## Web (session auth)

| Method | Path | Permission |
|--------|------|------------|
| GET | `/dashboard/workspace` | `dashboard.view` |
| GET | `/dashboard/api` | `dashboard.view` |
| GET | `/dashboard/widgets` | `dashboard.view` |
| GET | `/dashboard/widgets/{widgetKey}/data` | `dashboard.view` |
| POST | `/dashboard/widgets/{widget}/refresh` | `dashboard.view` |
| GET | `/dashboard/preferences` | `dashboard.view` |
| POST | `/dashboard/preferences` | `dashboard.customize` |
| DELETE | `/dashboard/preferences` | `dashboard.customize` |
| POST | `/dashboard/widgets/{widget}/hide` | `dashboard.customize` |
| POST | `/dashboard/widgets/{widget}/restore` | `dashboard.customize` |
| GET | `/dashboard/quick-actions` | `dashboard.view` |
| PATCH | `/dashboard/widgets/{widget}/organization` | `dashboard.manage` |
| PATCH | `/dashboard/quick-actions/{quickAction}/organization` | `dashboard.manage` |
| GET | `/dashboard/recent-activities` | `dashboard.view` |

## REST API (Sanctum)

Base: `/api/v1/dashboard`

Same endpoints as above under Sanctum + tenant middleware.

## Save layout payload

```json
{
  "layout": [
    {
      "widget_id": 1,
      "position_x": 0,
      "position_y": 0,
      "width": 6,
      "height": 4,
      "is_visible": true
    }
  ]
}
```
