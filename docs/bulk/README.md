# Bulk Operations Framework

NovaCRM Release **1.1.3** provides a centralized Enterprise Bulk Operations Framework.

## Architecture

```
Listing selection (page / ids / all)
        ↓
Bulk Operations Service
        ↓
Action Provider (module-registered)
        ↓
Sync or Queue (ProcessBulkOperationJob)
        ↓
Audit + Progress + Failure report
```

## Permissions

| Permission | Purpose |
|------------|---------|
| `bulk.view` | View history and progress |
| `bulk.manage` | Full management |
| `bulk.crm` | CRM bulk scope |
| `bulk.hrms` | HRMS bulk scope |
| `bulk.projects` | Projects bulk scope |
| `bulk.administration` | Administration bulk scope |
| `bulk.marketing` | Marketing bulk scope |

Actions also require their module permission (e.g. `leads.update`, `hrms.manage`).

## UI

- **Administration → Bulk Operations** — catalog + recent jobs
- Listing toolbars on **Leads** and **Employees** (extend via `<x-bulk.toolbar>`)

Flow: select records → choose action → configure inputs → confirm → execute → status page.

## Queue

- Threshold: `BULK_QUEUE_THRESHOLD` (default 25)
- Chunk size: `BULK_CHUNK_SIZE` (default 50)
- Max selection: `BULK_MAX_SELECTION` (default 10000)

## Registered actions (initial set)

### CRM
- `lead.assign_owner`, `lead.change_status`, `lead.delete`, `customer.delete`, `opportunity.delete`

### HRMS
- `employee.generate_login`, assign department/designation/branch, enable/disable portal, lock/unlock account

### Projects
- `project.change_status`, `task.change_priority`

### Administration
- `user.activate`, `user.disable`, `user.lock`, `user.unlock`, `user.resend_invitation`

## APIs

See [API docs](../api/bulk.md).

## Adding a new action

1. Implement `BulkActionProviderInterface`
2. Register in `AppServiceProvider::registerBulkActions()`
3. Pass `availableActionsFor()` into the listing view
4. Wrap the table with `<x-bulk.toolbar>`
