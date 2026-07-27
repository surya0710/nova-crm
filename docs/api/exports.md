# Exports API

Base path: `/api/v1/exports`

Middleware: `auth:sanctum`, organization context.

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/catalog` | Available exports grouped by module |
| `GET` | `/history` | Paginated export history |
| `POST` | `/generate` | Start an export |
| `GET` | `/sessions/{session}` | Status / progress |
| `GET` | `/sessions/{session}/download` | Download generated file |
| `DELETE` | `/sessions/{session}` | Delete export and file |

## Generate

```json
{
  "entity_type": "lead",
  "format": "xlsx",
  "selection_mode": "ids",
  "ids": [1, 2, 3],
  "filters": { "status": "new" },
  "columns": ["name", "email", "status", "assigned_owner"]
}
```

`selection_mode`: `ids` | `page` | `selected` | `filtered` | `all` | `complete`

## Permissions

Requires `exports.view` / `exports.create` / `exports.manage` plus module scope (`exports.crm`, …) and entity view permission.
