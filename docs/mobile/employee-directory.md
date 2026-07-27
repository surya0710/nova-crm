# Employee Directory APIs

Employee search and directory access for mobile.

---

## API Availability

| Feature | Endpoint | Status |
|---------|----------|--------|
| Employee search (picker) | `GET /api/v1/lookups/employees` | **Available** |
| Department filter | `GET /api/v1/lookups/departments` | **Available** |
| Branch filter | `GET /api/v1/lookups/branches` | **Available** |
| Designation filter | `GET /api/v1/lookups/designations` | **Available** |
| Full directory browse | — | **Not implemented** |
| Employee profile card | — | **Not implemented** |

---

## Lookup API — Employee Search

**Base:** `GET /api/v1/lookups/employees`  
**Controller:** `LookupApiController`  
**Service:** `EmployeeLookupService`

### Requirements

| Requirement | Value |
|-------------|-------|
| Permission | `hrms.view` |
| Module license | `hrms` |
| Middleware | Standard API stack |

> **Note:** `employee` role has `employee.directory` but lookups require `hrms.view`. Employees without `hrms.view` cannot use lookup API.

### Query Parameters

Validated by `LookupSearchRequest`:

| Param | Rules | Default |
|-------|-------|---------|
| `q` | nullable, string, max:200 | — |
| `page` | integer, min:1 | 1 |
| `per_page` | integer, min:1, max:50 | 20 |
| `id` | integer | Single-record resolve mode |

Config defaults (`config/lookups.php`):

- `per_page`: 20
- `max_per_page`: 50
- `min_search_length`: 0
- `cache_ttl_seconds`: 60

### Search Columns

`first_name`, `last_name`, `employee_code`, `email`, `mobile`

### Active Filter

Only employees with status `active` or `on_probation` (note: config uses `probation` in `clockable_employee_statuses` but lookup filters `on_probation` — verify in `EmployeeLookupService`).

### Response

```json
{
  "data": [
    {
      "id": 10,
      "label": "Jane Doe",
      "subtitle": "EMP042 · Senior Developer",
      "badge": "Engineering",
      "metadata": {
        "employee_code": "EMP042",
        "email": "jane@company.com",
        "mobile": "+1234567890",
        "department": "Engineering",
        "designation": "Senior Developer",
        "branch": "Head Office",
        "user_id": 15
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

### Single Record Resolve

```
GET /api/v1/lookups/employees?id=10
```

Returns array with 0 or 1 item.

### Example

```http
GET /api/v1/lookups/employees?q=jane&page=1&per_page=20 HTTP/1.1
Authorization: Bearer {token}
X-Organization-Id: 42
```

---

## Department / Branch / Designation Lookups

| Entity | Endpoint | Permission |
|--------|----------|------------|
| Departments | `/lookups/departments` | `hrms.view` |
| Branches | `/lookups/branches` | `hrms.view` |
| Designations | `/lookups/designations` | `hrms.view` |

Same query parameters and response structure.

Use department/branch lookups to build filter UI; employee lookup does not accept department/branch filter params directly — filter client-side or request backend enhancement.

---

## Web Directory (Not Available via API)

**Route:** `GET /hrms/directory`  
**Controller:** `EmployeeDirectoryController`  
**Permission:** `employee.directory`

Features not in lookup API:

- Paginated directory browse
- Filter by department, branch, designation
- Full profile card view
- Org chart navigation

Default `employee` role includes `employee.directory` for web access.

---

## Permissions Comparison

| Permission | Slug | Lookup API | Web directory |
|------------|------|------------|---------------|
| HRMS view | `hrms.view` | Required | — |
| Employee directory | `employee.directory` | Not used | Required |

Potential permission gap: employees with `employee.directory` but without `hrms.view` can browse web directory but not use API lookups.

---

## Pagination

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

Continue fetching while `has_more: true`.

---

## Sorting

Default order: `first_name` ascending (`EmployeeLookupService::orderByColumn()`).

No custom sort parameter exposed.

---

## Mobile Screen Mapping

```
Directory Screen
  ↓
GET /lookups/employees?q={search}
  ↓
[Profile detail — NOT AVAILABLE via API]
```

For profile detail, either:

1. Display lookup metadata only (limited)
2. Wait for `GET /api/v1/directory/employees/{id}` API
3. Deep-link to web directory (not ideal)

---

## Recommended Future API

```
GET /api/v1/directory/employees?q=&department_id=&branch_id=&page=
GET /api/v1/directory/employees/{id}
```

Permission: `employee.directory` (align with web).

Response should include public directory fields (no sensitive HR data).
