# API Response Format

Response conventions extracted from controllers across the NovaCRM API.

---

## Important: No Global Envelope

NovaCRM does **not** use a consistent `{ success, message, data }` wrapper on all endpoints. Response shape varies by controller.

---

## Common Patterns

### Pattern 1: Data Wrapper

Used by attendance, lookups, team summary:

```json
{
  "data": { }
}
```

### Pattern 2: Message + Data

Used by mutations (check-in/out, identity):

```json
{
  "message": "Checked in successfully.",
  "record": { },
  "dashboard": { }
}
```

### Pattern 3: Laravel API Resources

Used by tasks, leads, customers:

```json
{
  "data": [ ],
  "links": { },
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100
  }
}
```

### Pattern 4: Direct Object

Used by dashboard, widgets, RBAC:

```json
{
  "widgets": [ ],
  "permissions": [ ]
}
```

### Pattern 5: Mutation Success

Some CRM endpoints:

```json
{
  "success": true,
  "lead_id": 42,
  "message": "Lead created successfully."
}
```

---

## Success Responses

| Status | Usage |
|--------|-------|
| 200 | GET, successful mutations |
| 201 | Resource created (identity login-account, task attachment) |

Timestamps: ISO-8601 strings via `toIso8601String()` in API resources.

---

## Validation Error `422`

Laravel standard:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": [
      "Error message."
    ]
  }
}
```

Business rule errors also use 422 with field keys (e.g. `employee_id` for attendance blocks).

---

## Unauthorized `401`

Missing or invalid Sanctum token.

```json
{
  "message": "Unauthenticated."
}
```

---

## Forbidden `403`

Missing permission or lifecycle block.

```json
{
  "message": "This action is unauthorized."
}
```

Or lifecycle-specific:

```json
{
  "message": "API access is disabled for suspended organizations."
}
```

Module license:

```json
{
  "message": "Module not licensed."
}
```

---

## Not Found `404`

Resource not in current organization or route not found.

```json
{
  "message": "No query results for model [App\\Models\\Task] 42"
}
```

---

## Soft Empty State `200`

Not an error — user has access but no linked employee:

```json
{
  "message": "No employee record is linked to your account.",
  "empty_state": true,
  "audience": "employee"
}
```

From `MissingEmployeeRecordException`.

---

## Server Error `500`

```json
{
  "message": "Server Error"
}
```

In production, detailed stack traces are hidden.

---

## Maintenance Mode `503`

When `php artisan down` is active:

```json
{
  "message": "Service Unavailable"
}
```

---

## Rate Limiting `429`

`throttle:api` — 120 requests/minute per token or IP.

```json
{
  "message": "Too Many Attempts."
}
```

Headers:

```
X-RateLimit-Limit: 120
X-RateLimit-Remaining: 0
Retry-After: 60
```

---

## Lookup Pagination Meta

```json
{
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 100,
    "has_more": true
  }
}
```

---

## Recommended Client Normalization

Mobile clients may wrap responses internally:

```dart
class ApiResponse<T> {
  final bool success;
  final String? message;
  final T? data;
  final Map<String, List<String>>? errors;
}
```

Map per endpoint family — do not assume server returns this structure.

---

## Content Negotiation

Always send:

```http
Accept: application/json
```

Ensures JSON error responses instead of HTML redirects.

---

## Date/Time Format

- API resources: ISO-8601 (`2026-07-21T09:00:00+00:00`)
- Web shell notifications widget: human-readable (`2 hours ago`) in session endpoint only

Mobile should parse ISO-8601 from API endpoints.
