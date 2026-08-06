# P8 Phase 8.1 Impact Report — Import Platform Foundation

## Phase

Phase 8.1 — Import Platform Foundation

## Outcome

Konnect Nex now has a reusable **Import Platform** that owns file upload, spreadsheet
parsing, column detection, validation, preview, error reporting, and
tenant-scoped import sessions.

No Lead, Customer, or other entity import was implemented. Business entities
will plug in later through `ImportableEntityInterface` without modifying the
platform core.

## Import Platform Architecture

```text
Upload File
    |
    v
ImportSession (tenant-owned)
    |
    v
SpreadsheetReader          (CSV / XLSX)
    |
    v
ColumnDetector             (normalized header matching)
    |
    v
ImportValidationEngine     (row + header errors)
    |
    v
ImportPreview              (no persistence)
    |
    v
ImportErrorReportGenerator (CSV only)
    |
    v
Entity Adapter (future)    ← ImportableEntityInterface
    |
    v
Entity Service (future)
```

| Component | Responsibility |
| --- | --- |
| `ImportPlatformService` | Orchestration, session lifecycle, audit events |
| `SpreadsheetReader` | Detect worksheets, read headers, normalize rows |
| `ColumnDetector` | Auto-map headers to field keys |
| `ImportValidationEngine` | Required / type validation, unknown & duplicate columns |
| `ImportErrorReportGenerator` | Downloadable CSV error report |
| `ImportEntityRegistry` | Register entity adapters without platform changes |
| `ImportFieldDefinition` | Field key, label, required, type, metadata flag, aliases |
| `ImportableEntityInterface` | Entity contract: fields + persistence callback |

Namespace: `App\Services\Import\`

## Session Lifecycle

`ImportSession` tracks:

- organization, entity type, uploaded filename, uploaded by
- status, started_at, completed_at
- total / processed / created / updated / skipped / failed counts
- column mapping, detected headers, validation summary

Statuses:

| Status | Meaning |
| --- | --- |
| `uploaded` | File stored; not yet validated |
| `validating` | Parse / map / validate in progress |
| `ready` | Validation complete; preview available |
| `importing` | Reserved for future execution pipeline |
| `completed` | Reserved for future successful execution |
| `failed` | Terminal failure (e.g. unreadable file) |
| `cancelled` | Terminal cancellation |

Allowed transitions are enforced by `ImportSession::assertValidTransition()`.

Phase 8.1 exercises: `uploaded → validating → ready`, `uploaded → validating → failed`,
and `uploaded → cancelled`.

## Validation Flow

1. Resolve entity adapter from `ImportEntityRegistry`
2. Parse spreadsheet into canonical rows
3. Auto-detect column mapping from field definitions + aliases
4. Validate required fields and typed values (email, phone, date, number, integer, boolean)
5. Collect unknown columns and duplicate headers
6. Persist validation summary on the session
7. Produce preview (valid rows, invalid rows, row errors)

Validation never persists business entities. The adapter `persistRow()` callback
exists for later phases and is not invoked by the foundation.

## Spreadsheet Reader

Supported:

- CSV
- XLSX (`phpoffice/phpspreadsheet`)

Rejected:

- Legacy XLS
- Google Sheets

Reader responsibilities only: worksheet discovery, header reading, empty-row
skipping, canonical `{row_number, values}` collections.

## Column Detection

Headers are normalized (`Email Address` → `email_address`, `EMAIL` → `email`)
and matched against field key, label, and aliases.

Examples that map to `email`:

- Email
- email
- EMAIL
- Email Address (via alias)

Manual mapping UI is deferred to a later phase; overrides can later replace the
stored `column_mapping` JSON.

## Error Reporting

CSV-only error export columns:

- `row_number`
- `column`
- `field`
- `error`
- `original_value`

No PDF export.

## Security Model

- Every `ImportSession` belongs to exactly one organization (`BelongsToOrganization`)
- Explicit org lookups use `withoutGlobalScopes()` + `organization_id` filter
- Permissions added:
  - `imports.view`
  - `imports.create`
  - `imports.manage`
- Manager role receives import permissions; employee does not
- No shared cross-tenant import sessions

## Audit Logging

Reuses `AuditLogger` for:

| Event | When |
| --- | --- |
| `uploaded` | File upload creates session |
| `validated` | Validation succeeds → Ready |
| `validation_failed` | Unreadable / fatal parse failure |
| `preview_generated` | Preview built |
| `cancelled` | Session cancelled |

## How Future Entities Plug In

1. Implement `App\Contracts\Import\ImportableEntityInterface`
2. Return `ImportFieldDefinition` list (keys, labels, required, types, aliases, metadata flag)
3. Implement `persistRow()` to call the entity’s existing write service
4. Register the adapter on `ImportEntityRegistry` (e.g. from a service provider)

No Import Platform internals need to change for Lead, Customer, Organization,
Product, Vendor, Employee, or future modules.

Example (future, not shipped):

```php
$registry->register(app(LeadImportAdapter::class));
```

## What Was Added

- `app/Services/Import/*`
- `app/Contracts/Import/ImportableEntityInterface.php`
- `app/Models/ImportSession.php`
- `database/migrations/2026_07_17_000001_create_import_sessions_table.php`
- `database/migrations/2026_07_17_000002_add_imports_permissions.php`
- `database/factories/ImportSessionFactory.php`
- `config/import.php`
- `tests/Feature/ImportSpreadsheetReaderTest.php`
- `tests/Feature/ImportValidationEngineTest.php`
- `tests/Feature/ImportPlatformSessionTest.php`
- `tests/Support/FakeImportableEntity.php`
- Dependency: `phpoffice/phpspreadsheet`

## What Did Not Change

Frozen platforms were not modified:

- CRM Core (Leads, Customers, Organizations, Opportunities, Quotations, Invoices, Payments)
- Metadata Platform
- Marketing Platform
- Provider Platform
- Revenue Platform

Out of scope (deferred):

- Lead / Customer import
- Entity persistence / duplicate detection / update existing
- Queue workers / background processing / scheduled imports
- Import templates / rollback
- Manual field mapping UI / import history page

## Testing Summary

Import Platform Suite:

| Suite | Result |
| --- | --- |
| `ImportSpreadsheetReaderTest` | Pass |
| `ImportValidationEngineTest` | Pass |
| `ImportPlatformSessionTest` | Pass |
| Import filter total | **22 passed (89 assertions)** |

CRM-focused filter (`Lead|Customer|Organization|Opportunity|Quotation|Invoice|Payment|Revenue`):

| Result |
| --- |
| **98 passed (276 assertions)** |

Full suite gate:

| Metric | Before (Phase 7E.1 gate) | After Phase 8.1 |
| --- | --- | --- |
| Tests | 713 | **735** |
| Assertions | 2691 | **2780** |
| Failures | 0 | **0** |

Coverage includes:

- CSV / XLSX reading, empty file, malformed file, XLS rejection
- Required fields, invalid typed values, duplicate columns, unknown columns
- Session lifecycle, status transitions, audit logging
- Tenant isolation, unauthorized employee permissions
- Confirmation that entity `persistRow()` is never called
- Zero regressions across CRM, Metadata, Marketing, Provider, and Revenue suites

## Completion Checklist

- [x] Import Platform namespace and orchestration service
- [x] Import sessions with tenant ownership
- [x] CSV + XLSX support
- [x] Spreadsheet parsing and worksheet detection
- [x] Column auto-detection
- [x] Validation engine
- [x] Preview generation
- [x] CSV error reporting
- [x] Audit logging
- [x] Multi-tenancy enforcement
- [x] Entity adapter contract (no production adapters)
- [x] Comprehensive Feature tests
- [x] Impact report
- [x] Full suite zero-regression gate (735 / 2780 / 0 failures)
