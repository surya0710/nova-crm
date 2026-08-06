# Bulk Operations API

Base path: `/api/v1/bulk`

Auth: Sanctum + organization context. RBAC enforced.

| Method | Path | Description |
|--------|------|-------------|
| GET | `/actions/{entity}` | Actions available to the current user |
| POST | `/execute` | Start a bulk operation (`confirm` required) |
| GET | `/history` | Paginated operations |
| GET | `/operations/{id}` | Status / progress |
| GET | `/operations/{id}/errors` | Download failure CSV |

### Execute body

```json
{
  "action_key": "lead.change_status",
  "selection_mode": "ids",
  "ids": [1, 2, 3],
  "input": { "status": "contacted" },
  "confirm": true
}
```

`selection_mode`: `ids` | `page` | `all` | `filtered`
