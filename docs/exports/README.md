# Universal Export Center

NovaCRM Release **1.1.4** provides a centralized Universal Export Center.

## Architecture

```
Export Center / Bulk toolbar / API
        ↓
Export Platform Service
        ↓
Export Definition (module-registered adapter)
        ↓
Query Builder (selection + filters)
        ↓
Formatter (CSV / Excel / PDF)
        ↓
Sync or Queue (ProcessExportSessionJob)
        ↓
Secure download + Audit
```

Modules register export definitions instead of implementing custom export logic.

## Permissions

| Permission | Purpose |
|------------|---------|
| `exports.view` | View catalog, history, and downloads |
| `exports.create` | Generate exports |
| `exports.manage` | Revoke, regenerate, and delete |
| `exports.crm` | CRM export scope |
| `exports.hrms` | HRMS export scope |
| `exports.projects` | Projects export scope |
| `exports.marketing` | Marketing export scope |
| `exports.administration` | Administration export scope |

Entity adapters also require their module view permission (e.g. `leads.view`, `hrms.view`).

## Formats

| Format | Notes |
|--------|-------|
| Excel (`.xlsx`) | PhpSpreadsheet writer |
| CSV | UTF-8 with BOM, streamed |
| PDF | DomPDF with organization branding |

Architecture allows future JSON, XML, and ZIP writers without changing adapters.

## Selection sources

- Current page / selected IDs
- Filtered dataset
- Complete dataset

Listing toolbars (Leads, Employees) expose **Export** beside bulk actions.

## Queue

- Threshold: `EXPORT_QUEUE_THRESHOLD_ROWS` (default 100)
- Chunk size: `EXPORT_CHUNK_SIZE` (default 250)
- Max records: `EXPORT_MAX_RECORDS` (default 500000)
- PDF max rows: `EXPORT_PDF_MAX_ROWS` (default 2000)
- Download TTL: `EXPORT_DOWNLOAD_TTL_HOURS` (default 72)

## Security

- Organization isolation on every session and download
- Sensitive columns (passwords, tokens) never leave adapters
- Download links expire and can be revoked by administrators
- Files stored under `{disk}/exports/{organization_id}/`

## Audit events

`export_started`, `export_queued`, `export_completed`, `export_failed`, `export_downloaded`, `export_revoked`, `export_deleted`

## UI

- **Administration → Export Center** — catalog + recent jobs
- Entity create form — format, columns, complete dataset
- Listing bulk toolbar — select → Export → format → generate

## APIs

See [API docs](../api/exports.md).

## Adding a new export entity

1. Implement `ExportableEntityInterface` (extend `AbstractExportAdapter`)
2. Add catalog entry in `config/export.php`
3. Register in `AppServiceProvider::registerExportAdapters()`
4. Optionally enable `<x-bulk.toolbar export-enabled="true">` on the listing

## Troubleshooting

| Issue | Check |
|-------|-------|
| Empty catalog | Module license + `exports.*` permissions |
| Queue stuck | Queue workers (`SOP-DEP-004`) |
| PDF too large | Use CSV/Excel; raise `EXPORT_PDF_MAX_ROWS` only carefully |
| Download 404 | Expiry, revoke, or missing file on disk |
