# Universal Search APIs

Search endpoints available to mobile clients.

---

## Lookup Platform (Primary Search)

**Endpoint:** `GET /api/v1/lookups/{entity}`

See [employee-directory.md](./employee-directory.md) for full details.

### Available Entities

| Entity | Permission | Module | Use case |
|--------|------------|--------|----------|
| `users` | none | — | User search |
| `employees` | `hrms.view` | `hrms` | Employee search |
| `departments` | `hrms.view` | `hrms` | Department filter |
| `designations` | `hrms.view` | `hrms` | Designation filter |
| `branches` | `hrms.view` | `hrms` | Branch filter |
| `shifts` | `hrms.view` | `hrms` | Shift definitions |

### Query Parameters

| Param | Rules |
|-------|-------|
| `q` | nullable, string, max:200 |
| `page` | integer, min:1 |
| `per_page` | integer, min:1, max:50 |
| `id` | integer (single resolve) |

### Response Format

```json
{
  "data": [
    {
      "id": 1,
      "label": "Display Name",
      "subtitle": "Secondary text",
      "badge": "Category badge",
      "metadata": { }
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 100,
    "has_more": true
  }
}
```

---

## Task Search

Via tasks list endpoint with `search` parameter:

```
GET /api/v1/tasks?search={query}
```

Requires `api.access` + `tasks.view`.

Searches: `title`, `description`, `task_number`

---

## Project Search

Via projects list endpoint (controller supports search/filter params):

```
GET /api/v1/projects?search={query}
```

Requires `api.access` + `projects.view`.

---

## Document Search

**Not available** via API.

---

## Sorting

| API | Sort support |
|-----|--------------|
| Lookups | Fixed per service (e.g. `first_name` for employees) |
| Tasks | Default Laravel ordering |
| Projects | Default Laravel ordering |

No client-specified sort parameter for lookups.

---

## Filters

| API | Filters |
|-----|---------|
| Lookups | `q` text search only |
| Tasks | `status`, `priority`, `assigned_to`, `project_id`, `filter=overdue` |
| Projects | Various index params |
| Employees (lookup) | Active employees only — no department/branch filter param |

---

## Pagination

### Lookups

```json
{
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 150,
    "has_more": true
  }
}
```

### Tasks / Projects

Standard Laravel pagination:

```json
{
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 142
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

---

## Caching

Lookup empty-query page-1 results cached for `lookups.cache_ttl_seconds` (default 60s) per org/entity.

---

## Web Duplicate

`GET /shell/lookups/{entity}` — same format, session auth.

---

## Authorization Flow

`LookupPlatformService::assertAuthorized()`:

1. Entity exists in `config/lookups.php`
2. User belongs to organization
3. Module licensed (if `license_module` set)
4. Permission check (owners/super-admins bypass)

---

## Mobile Search UX Recommendations

| Screen | API |
|--------|-----|
| Employee search | `/lookups/employees?q=` |
| Department picker | `/lookups/departments?q=` |
| Task search | `/tasks?search=` |
| Project search | `/projects?search=` |

Debounce input 300ms (`lookups.debounce_ms` config default).

---

## Not Available

- Global unified search across all entity types
- Full-text document search
- Leave application search
- Attendance record search
