# Lookup API

Base path: `/api/v1/lookups`

Authentication: Sanctum (`Authorization: Bearer` or session cookie)

Organization context: `X-Organization-Id` header (required)

## Search

```
GET /api/v1/lookups/{entity}?q=&page=1&per_page=20
```

### Parameters

| Name | Type | Description |
|------|------|-------------|
| `q` | string | Search query (optional) |
| `page` | int | Page number (default 1) |
| `per_page` | int | Results per page (max from config) |
| `id` | int | Resolve a single record by ID |

### Entities

- `users`
- `employees`
- `departments`
- `designations`
- `branches`
- `shifts`

### Example

```http
GET /api/v1/lookups/users?q=jane&page=1
X-Organization-Id: 1
Authorization: Bearer {token}
```

### Response

```json
{
  "data": [
    {
      "id": 5,
      "label": "Jane Doe",
      "subtitle": "jane@example.com",
      "badge": "Employee",
      "metadata": {
        "email": "jane@example.com",
        "role": "Employee"
      }
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 1,
    "has_more": false
  }
}
```

## Web endpoint (session auth)

```
GET /shell/lookups/{entity}
```

Same query parameters and response format. Used by the Entity Picker in Blade views.

## Errors

| Status | Cause |
|--------|-------|
| 403 | Missing permission or module not licensed |
| 404 | Unknown entity or missing organization context |
| 422 | Invalid organization context (API) |
