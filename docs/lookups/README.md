# Lookup Platform

NovaCRM Release **1.1.S.1** provides a reusable Lookup Platform for organization-scoped entity search.

## Architecture

```
UI (Entity Picker / Bulk Toolbar)
        ↓
Lookup API (web + REST)
        ↓
LookupPlatformService (RBAC + licensing)
        ↓
LookupRegistry → Entity Lookup Service
        ↓
Organization-scoped query
```

## Registered entities

| Entity | Endpoint | Permission |
|--------|----------|------------|
| `users` | `/api/v1/lookups/users` | Org member |
| `employees` | `/api/v1/lookups/employees` | `hrms.view` |
| `departments` | `/api/v1/lookups/departments` | `hrms.view` |
| `designations` | `/api/v1/lookups/designations` | `hrms.view` |
| `branches` | `/api/v1/lookups/branches` | `hrms.view` |
| `shifts` | `/api/v1/lookups/shifts` | `hrms.view` |

Web session endpoint: `GET /shell/lookups/{entity}` (`shell.lookups.search`).

## Response format

```json
{
  "data": [
    {
      "id": 12,
      "label": "John Smith",
      "subtitle": "Sales Manager",
      "badge": "Sales",
      "metadata": {
        "email": "john@example.com",
        "department": "Sales"
      }
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 42,
    "has_more": true
  }
}
```

## Configuration

See `config/lookups.php`:

- `per_page`, `max_per_page`
- `min_search_length`
- `debounce_ms`
- `cache_ttl_seconds`
- Entity registry (`entities`, `bulk_field_types`, `bulk_type_entities`)

## Adding a new lookup entity

1. Create a service extending `AbstractLookupService`
2. Register the service in `AppServiceProvider::registerLookups()`
3. Add entity metadata to `config/lookups.php`
4. No controller changes required

## Security

- Organization isolation on every query
- RBAC per entity (`permission` in config)
- Module licensing via `license_module`
- Active records only (where applicable)
- Disabled users excluded from user lookup

## Related docs

- [API reference](../api/lookups.md)
- [Entity Picker component](../components/entity-picker.md)
- [Bulk integration guide](./integration.md)
