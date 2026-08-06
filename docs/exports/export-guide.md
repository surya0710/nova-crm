# Export Guide

## When to use Export Center

Use **Administration → Export Center** for organization-scoped Excel, CSV, and PDF extracts across CRM, HRMS, Projects, Marketing, and Administration.

## Quick flows

### Full entity export

1. Open Export Center
2. Choose an entity (e.g. Leads)
3. Select format and columns
4. Generate → wait for completion → Download

### Selected records (listing)

1. Open Leads or Employees
2. Select page / records via the bulk toolbar
3. Click **Export** → choose format → **Generate file**

### Filtered export

Pass current listing filters through the toolbar (automatic) or API `filters` payload. Only matching organization-scoped rows are included.

## Formats

| Format | Best for |
|--------|----------|
| Excel | Spreadsheet review, formulas later |
| CSV | Large datasets, BI tools |
| PDF | Short branded reports (row-capped) |

## Security notes

- Downloads expire after `EXPORT_DOWNLOAD_TTL_HOURS`
- Admins can revoke or delete exports
- Passwords and other sensitive adapter columns are never exported
- Files never cross organization boundaries

## Queue processing

Exports above `EXPORT_QUEUE_THRESHOLD_ROWS` run via `ProcessExportSessionJob`. Ensure queue workers are running (see SOP-DEP-004).
