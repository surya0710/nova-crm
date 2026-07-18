# P9 Phase 9.1.2 Impact Report — Import Validation Report & UX Stabilization

## Phase

Phase 9.1.2 — Import Validation Report (Lead & Customer Import UX Enhancement)

## Outcome

When an import contains validation errors, users can now download a complete,
human-readable **Validation Report** (CSV, with an optional Excel version)
instead of scanning errors only in the browser. The report lists one row per
validation error with the source row number, column, the imported value, and a
readable error message, preceded by a summary block.

This phase is **UX-only and strictly additive**. No import logic, validation
logic, migrations, tenant data, users, or organizations were changed. The
report is generated on demand from errors the Import Platform already stores on
the session — it never re-runs validation and never persists anything.

## New Behaviour

```text
Upload → Preview → Validation Errors → Download Validation Report (CSV/Excel)
       → Correct Spreadsheet → Re-import
```

The **Download Validation Report** button appears on the Preview page only when
validation errors exist. When validation passes, no button is shown.

## Service Architecture

```text
Preview page (Download Validation Report)
        |
        v
LeadImportController / CustomerImportController
  ::validationReport (CSV) / ::validationReportXlsx (Excel)
        |
        v
ImportValidationReportService          (entity-agnostic)
        |
        +-- ImportSession.validation_summary['errors']   (already materialized)
        +-- ImportEntityRegistry → adapter->fieldDefinitions()  (field key → label)
        +-- ImportSession.organization / total_rows / valid_rows / invalid_rows
```

| Component | Responsibility |
| --- | --- |
| `ImportValidationReportService` | Build ordered report rows + summary; emit CSV / XLSX; stream downloads |
| `ImportEntityRegistry` | Resolves the entity adapter to map field keys → human-readable labels |
| `ImportSession.validation_summary['errors']` | Canonical, already-persisted error list (single source of truth) |

Namespace: `App\Services\Import\ImportValidationReportService`

The service is registered implicitly through container auto-resolution and
depends only on the shared singleton `ImportEntityRegistry` (the same instance
used by `ImportPlatformService`), so it reuses the identical registered adapters
and field definitions.

## CSV Format

The CSV is UTF-8 with a BOM (Excel/LibreOffice friendly) and has two sections:

### Summary block (top)

```text
Import Validation Report
Organization,<current organization name>
Generated,<YYYY-MM-DD HH:MM:SS>
Import Session,<original filename> (#<session id>)
Rows Processed,<total rows>
Rows Valid,<valid rows>
Rows Invalid,<invalid rows>
Total Errors,<error count>
```

A blank spacer row separates the summary from the error table.

### Error table

| Column | Source |
| --- | --- |
| Row Number | Spreadsheet row number (header is row 1, first data row is row 2) |
| Column | Original spreadsheet header when known, else the field label, else field key |
| Imported Value | The offending value as it appeared in the file |
| Error Message | Human-readable validation message |

One row per validation error. Multiple errors are never combined into one cell.

## Error Generation

The service reads `validation_summary['errors']` — the exact error list the
Import Platform produced during `validate()` (type validation, required/unknown/
duplicate columns, plus entity errors merged via `validateMappedRows()`:
duplicates, owner resolution, lookup resolution, metadata validation).

Column resolution for each error:

1. `column` (original spreadsheet header) when present — used by type-validation errors.
2. Otherwise the field label resolved from the adapter's `fieldDefinitions()` (e.g. `owner` → `Owner`).
3. Otherwise the raw field key, or empty when the error is not tied to a field.

No new error types are introduced; the report only reformats existing errors.

## Error Ordering

Deterministic ordering is applied with a stable composite sort:

```text
Row Number ASC → Column Name ASC (case-insensitive) → Error Message ASC
```

The trailing error-message tiebreaker keeps output stable when a single row/column
has multiple messages.

## Summary Section

| Field | Value |
| --- | --- |
| Organization | Current organization name (from the session's organization) |
| Generated | Report generation timestamp |
| Import Session | Original filename + session id |
| Rows Processed | `total_rows` |
| Rows Valid | `validation_summary['valid_rows']` |
| Rows Invalid | `validation_summary['invalid_rows']` |
| Total Errors | Count of stored validation errors |

## Excel (XLSX) Version

Because the Import Platform already ships PhpSpreadsheet (used by
`ImportTemplateService`), an Excel report is generated too, without duplicating
CSV logic — both formats share `buildRows()` and `summary()`.

| Sheet | Contents |
| --- | --- |
| `Summary` | Title + summary key/value block |
| `Validation Errors` | Header row + one row per error (values written as explicit strings) |

Routes: `…/report` (CSV) and `…/report/xlsx` (Excel).

## Performance Considerations

- The report is built from errors **already stored** on the session; the source
  spreadsheet is not re-read to generate the report.
- Only errors (i.e. issues on invalid rows + column-level problems) are
  materialized — not every row — so a 10,000-row file with few errors produces a
  small report.
- CSV downloads stream directly to `php://output` via `fputcsv`, so the CSV body
  is never fully buffered in memory.
- Sorting is `O(n log n)` over the error list only.

## Multi-tenancy

- The controller authorizes and resolves the session with
  `findForOrganization()` (tenant-scoped) before the service is invoked;
  cross-tenant session ids return 404.
- Field labels come from the registered adapter, whose metadata fields are scoped
  to the current `TenantContext` organization.
- The summary organization name is read from the session's own organization.
- No other organization's metadata, users, lookup values, or data can appear in
  a report.

## Security Model

- Same gate as the rest of the import flow: entity `create` ability +
  `imports.view` (via `authorizeSession`).
- Employees without import permission receive 403.
- No new RBAC permissions were added.

## Routes / UI

```text
GET /imports/leads/{session}/report        → leads.import.report        (CSV)
GET /imports/leads/{session}/report/xlsx   → leads.import.report.xlsx   (Excel)
GET /imports/customers/{session}/report      → customers.import.report      (CSV)
GET /imports/customers/{session}/report/xlsx → customers.import.report.xlsx (Excel)
```

Preview page:

- Stat cards now show **Rows, Valid, Invalid, Errors, Duplicates**.
- **Download Validation Report** and **Download Validation Report (Excel)**
  buttons appear only when errors exist.
- Existing **Download error report** link and **Import** / **Cancel** actions are
  unchanged.

## What Did Not Change

- Import Platform parse / validate / execute pipeline
- `ImportValidationEngine`, `SpreadsheetReader`, `ColumnDetector`
- `LeadImportAdapter` / `CustomerImportAdapter` validation, duplicate detection,
  owner resolution, metadata validation, and persistence
- Assignment, Metadata, Marketing, and Provider platforms
- Existing migrations, Users, Organizations, Import Sessions, and seed data

Additive only:

- `ImportValidationReportService`
- `report` / `report.xlsx` routes + controller actions for Lead and Customer
- Preview view buttons and an Errors stat card

## Future Extension Points

- Any future importable entity automatically gains reports: register its adapter
  on `ImportEntityRegistry`, add `report` routes/actions, and reuse the service.
- The XLSX writer can later gain styling/auto-filter without touching the CSV path.
- If a session ever lacks stored errors, a fallback (regenerating the preview via
  `ImportPlatformService`) can be added; today validated sessions always store
  `validation_summary['errors']`.

## Testing Summary

| Suite | Result |
| --- | --- |
| `ImportValidationReportTest` | **13 passed (58 assertions)** |
| Import Platform filter (`Import\|LeadImport\|CustomerImport`) | **73 passed (389 assertions)** |
| Full suite | See completion gate below |

Coverage:

- CSV generated with BOM, summary block, and correct table headers
- Summary counts (processed / valid / invalid / total errors)
- Correct row numbers, column names, imported values, and messages
- Column resolution: original header (type errors) and field-key → label (entity errors)
- Deterministic ordering (Row ASC → Column ASC)
- Multiple errors per row, duplicate rows, metadata errors, owner errors, lookup errors
- No errors when all rows valid (button hidden)
- XLSX report with Summary + Validation Errors sheets
- Download button visibility (shown with errors, hidden when valid)
- Tenant isolation (cross-tenant 404) and authorization (employee 403)
- Customer Import report parity

## Completion Checklist

- [x] `ImportValidationReportService` exists and is reusable/entity-agnostic
- [x] Validation report downloads successfully (CSV)
- [x] Optional XLSX report generated (shared logic, no duplication)
- [x] Report contains all validation errors
- [x] Correct row numbers / column names / imported values / messages
- [x] Deterministic ordering (Row ASC → Column ASC)
- [x] Summary block included
- [x] Download button appears only when errors exist
- [x] Multi-tenancy enforced
- [x] Authorization enforced (no new permissions)
- [x] Import behaviour unchanged
- [x] No existing tenant data / users / organizations modified
- [x] No migrations altered
- [x] Comprehensive tests (validation report + import suites green)
- [x] Documentation completed
