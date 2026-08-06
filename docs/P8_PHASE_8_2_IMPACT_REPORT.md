# P8 Phase 8.2 Impact Report — Lead Import

## Phase

Phase 8.2 — Lead Import

## Outcome

Konnect Nex can import Leads from CSV and XLSX through the existing Import Platform.
Lead-specific rules live in `LeadImportAdapter`; persistence goes through
`LeadService`. The Import Platform remains generic.

## Lead Adapter Architecture

```text
Leads Index → Import button
        ↓
LeadImportController (upload / preview / execute / summary)
        ↓
ImportPlatformService (unchanged orchestration + executeImport)
        ↓
LeadImportAdapter  ← ImportableEntityInterface
        ↓
LeadService::create + MetadataEntityFormService
        ↓
Lead (+ optional LeadNote) + Audit
```

| Piece | Role |
| --- | --- |
| `LeadImportAdapter` | Field definitions, metadata exposure, row validation, `persistRow()` |
| `ImportPlatformService::executeImport()` | Reserved pipeline completed: ready → importing → completed |
| `ImportableEntityInterface::validateMappedRows()` | Additive hook for entity-specific validation |
| `LeadImportController` + `resources/views/imports/leads/*` | Upload → Preview → Import → Summary UI |

## How Little the Import Platform Changed

Only additive completions of the Phase 8.1 design:

1. **`executeImport()`** — statuses `importing` / `completed` were already reserved
2. **`validateMappedRows()`** on the entity contract — merges entity errors after type validation
3. **`ImportValidationEngine::mergeEntityErrors()`** — helper to recalculate valid/invalid counts
4. **Registry registration** of `LeadImportAdapter` in `AppServiceProvider::boot()`

No changes to spreadsheet reading, column detection, session tenancy, or error CSV format.

## Supported Fields

Standard:

- First Name / Last Name / Full Name (name required via composition)
- Email, Phone, Company
- Organization → maps to **company** (tenant org is always the current organization)
- Source, Status (Pipeline / Stage are aliases for Lead **status**; Leads have no pipeline model)
- Owner, Priority, Industry, Budget, Notes

Metadata:

- Active Lead metadata definitions for the current tenant are exposed as import fields
- Values validated/persisted through `MetadataEntityFormService` (no Metadata Platform changes)

## Duplicate Detection

Reported during validation (not created):

- Primary: matching email against open leads in the same organization
- Secondary: matching phone against open leads in the same organization
- Within-file duplicate email or phone

Execution treats duplicate rows as **skipped**. Summary shows a dedicated Duplicate count from `validation_summary.duplicate_rows`.

No merge / update-existing behavior.

## Owner Resolution

Resolves `Owner` against organization members by:

1. Email (case-insensitive)
2. Full name (exact, case-insensitive; ambiguous names fail)

Employee codes are not available on `User` and are not resolved. Unknown owners are validation errors.

## Lookup Resolution

| Column | Resolution |
| --- | --- |
| Source | `config('leads.sources')` key or label |
| Status / Pipeline / Stage | `config('leads.statuses')` key or label |
| Priority | `config('leads.priorities')` key or label |
| Organization | Alias for Company (string on Lead) |

Defaults when blank: `source=import`, `status=new`, `priority=medium`.

Unknown lookups are row validation errors. No auto-create of config lookups (Lead workflow never auto-created those).

## Metadata Integration

- Field keys come from `MetadataFieldDefinition.key`
- Types map into Import Platform data types (email, phone, date, number, boolean, string)
- Persistence uses `validatedValues()` + `persistValidatedValues()` on create context
- Metadata Platform contracts untouched

## Security Model

- Import sessions remain organization-owned
- HTTP flow requires `Lead` create ability + `imports.create` / `imports.view`
- Cross-tenant session access returns 404 via `findForOrganization`
- Duplicate checks are tenant-scoped
- Owner resolution limited to current organization members

## Audit Logging

Session-level (Import Platform + Lead execute):

| Event | When |
| --- | --- |
| `uploaded` / `validated` / `preview_generated` | Existing foundation events |
| `import_started` | Execute begins |
| `import_completed` | Execute finishes with counts |
| `import_failed` | Fatal execute failure |

Per-lead `created` continues via the Lead `Auditable` trait through `LeadService::create`.

## UI

- **Import** button on Leads index (requires create + `imports.create`)
- Routes under `/imports/leads/*`
- Screens: upload, preview (mapping + row errors), summary (Created / Skipped / Failed / Duplicate)
- CSV error report download

## Testing Summary

| Suite | Result |
| --- | --- |
| `LeadImportTest` | **11 passed** (included in full gate) |
| Import Platform suite | **22 passed (89 assertions)** |
| Full suite | **746 passed (2840 assertions), 0 failures** |

Prior gate: 735 / 2780 / 0. Delta: +11 tests, +60 assertions.

Coverage:

- Valid CSV / XLSX import via `LeadService`
- Metadata field import
- Owner resolution (email/name) and unknown owner
- Source/status lookup resolution
- Duplicate detection (existing + within file)
- Invalid email/phone
- Tenant isolation
- HTTP workflow + unauthorized employee

## What Did Not Change

- CRM Core write contracts (beyond using `LeadService`)
- Metadata Platform
- Marketing Platform
- Provider Platform
- Revenue Platform
- Import Platform architecture (additive hooks only)

## Out of Scope (Deferred)

- Customer / Contact import
- Update existing leads / merge duplicates
- Queue workers / background / scheduled imports
- Undo import

## Completion Checklist

- [x] `LeadImportAdapter` implements `ImportableEntityInterface`
- [x] CSV and XLSX Lead import
- [x] Metadata fields supported
- [x] Duplicate detection
- [x] Owner resolution
- [x] Lookup resolution
- [x] Persistence via `LeadService`
- [x] Audit logging
- [x] Tenant isolation
- [x] Import Platform remains generic
- [x] Comprehensive tests
- [x] Impact report
- [x] Full suite zero-regression gate (746 / 2840 / 0 failures)
