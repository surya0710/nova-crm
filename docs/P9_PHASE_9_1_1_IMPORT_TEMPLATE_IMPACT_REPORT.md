# P9 Phase 9.1.1 Impact Report — Import Template Generator (Lead)

## Phase

Phase 9.1.1 — Import Template Generator (Lead Import UX Enhancement)

## Outcome

Lead Import users can download organization-scoped CSV and Excel templates before
uploading a file. Templates are generated on demand (never stored) and include
standard Lead columns, active tenant metadata fields, one sample row, Excel
instructions, and dynamic lookup values.

This phase is **UX-only and strictly additive**. Existing Import Platform
behaviour, migrations, Users, Organizations, and other platforms were not
redesigned or modified beyond a small additive helper on `ImportOwnerResolver`.

## Architecture

```text
Leads Index (Import ▼) / Import Leads page
        |
        v
LeadImportController::downloadCsvTemplate / downloadXlsxTemplate
        |
        v
ImportTemplateService          (entity-agnostic)
        |
        v
LeadImportTemplateAdapter      ← ImportTemplateProviderInterface
        |
        +-- MetadataEntityFormService   (active lead fields + options)
        +-- ImportOwnerResolver         (listMembers / Owner matching set)
        +-- config('leads.*')           (status / source labels)
```

| Component | Responsibility |
| --- | --- |
| `ImportTemplateProviderInterface` | Entity contract: columns, sample, lookups, instructions |
| `ImportTemplateService` | CSV / XLSX generation, headers, sample row, workbook sheets |
| `ImportTemplateColumn` / `ImportTemplateLookupGroup` | Immutable value objects |
| `LeadImportTemplateAdapter` | Lead columns, metadata, samples, lookups, instructions |
| `ImportOwnerResolver::listMembers()` | Additive shared owner list (same membership set as resolve) |

Namespace: `App\Services\Import\` (+ `Adapters\LeadImportTemplateAdapter`)

## Template Generation Flow

1. Authorize with existing Lead create + `imports.create` (no new permissions)
2. Resolve current organization from `TenantContext`
3. Adapter builds columns: standard Lead labels → active metadata fields
4. Adapter builds one realistic sample row (Owner from org members when present)
5. Service emits:
   - **CSV**: UTF-8 BOM + header + sample (Excel/LibreOffice friendly)
   - **XLSX**: three sheets via PhpSpreadsheet (same library as Import Platform)

No background jobs. No caching. No persisted template files. Expected generation
time is well under one second for typical tenant field counts.

## Sheet Layout (Excel)

| Sheet | Contents |
| --- | --- |
| `Lead Import` | Headers + one sample row |
| `Instructions` | Required/optional fields, duplicates, owner matching, formats, max size, metadata, blank-Owner → Assignment |
| `Lookup Values` | Status, Source, Owners, metadata dropdown options / format hints |

## Standard Columns

Exactly the Lead Import labels (no aliases introduced):

First Name, Last Name, Email, Phone, Company, Status, Source, Owner, Notes

## Metadata Integration

- Reuses `MetadataEntityFormService::fieldsFor($org, 'lead', 'create')`
- Only **active** tenant definitions are included
- Order: standard fields, then metadata fields
- Dropdown / select / radio: active options on Lookup Values; sample uses option **value**
- Text: field name listed; free-text note on Lookup Values
- Date: `YYYY-MM-DD` example
- Numeric: sample `100`

No Metadata Platform changes.

## Lookup Generation

| Group | Source |
| --- | --- |
| Status | `config('leads.statuses')` labels |
| Source | `config('leads.sources')` labels |
| Owner | `ImportOwnerResolver::listMembers($organization)` |
| Metadata | Active field options / format notes |

Never hardcoded tenant-specific values. Inactive metadata options are excluded.

## Sample Row Generation

One realistic example (e.g. John / Doe / john@example.com / Website / New).
Owner prefers an existing organization member name when available.
Metadata samples are type-aware and use real option values when present.

## Security Model

- Same authorization as Lead Import upload: `authorize('create', Lead)` + `imports.create`
- Users without import permission cannot download templates
- No new RBAC permissions

## Multi-tenancy Behaviour

All content is organization-scoped:

- Metadata definitions and options for the current org only
- Owner list from current org membership only
- Organization A never receives Organization B fields, users, or lookups

## Routes / UI

```text
GET /imports/leads/template/csv   → leads.import.template.csv
GET /imports/leads/template/xlsx  → leads.import.template.xlsx
```

UI:

- Leads index: **Import ▼** dropdown (Excel template, CSV template, Import Leads)
- Import Leads page: Download Excel / Download CSV above the upload form

## What Did Not Change

- Import Platform parse / validate / execute pipeline
- `LeadImportAdapter` persistence / validation rules
- Customer Import
- Assignment Platform (instructions reference blank-Owner behaviour only)
- Metadata / Marketing / Provider platforms
- Existing migrations, Users, Organizations, seed data

Additive only:

- `ImportOwnerResolver::listMembers()` (shared listing; resolve logic unchanged)

## Future Extension Points

Customer, Product, Employee, or Vendor templates reuse the same service:

1. Implement `ImportTemplateProviderInterface` (e.g. `CustomerImportTemplateAdapter`)
2. Inject provider into the entity import controller
3. Call `ImportTemplateService::downloadCsv` / `downloadXlsx`
4. Add routes + UI download links

No changes to `ImportTemplateService` are required for new entities.

## Testing Summary

| Suite | Result |
| --- | --- |
| `LeadImportTemplateTest` | **6 passed (65 assertions)** |
| Import Platform filter (`Import\|LeadImport\|CustomerImport`) | **60 passed (331 assertions)** |
| Full suite | **786 passed (3041 assertions), 0 failures** |

Coverage:

- CSV download with headers, sample row, metadata columns, UTF-8 BOM
- Excel workbook with Lead Import / Instructions / Lookup Values sheets
- Metadata text, date, and dropdown (active options only)
- Tenant isolation for metadata and owners
- Authorization (employees cannot download)
- Inactive metadata excluded
- Regression: Lead Import, Customer Import, Assignment intake, Meta import

## Completion Checklist

- [x] CSV template downloads successfully
- [x] Excel template downloads successfully
- [x] Standard Lead columns included
- [x] Tenant metadata fields included
- [x] One realistic sample row
- [x] Lookup Values sheet dynamically generated
- [x] Instructions sheet exists
- [x] Assignment-aware Owner list (`listMembers` + blank-Owner instructions)
- [x] Multi-tenancy enforced
- [x] No existing Import behaviour changes
- [x] No Users / Organizations modified or deleted
- [x] No migrations altered
- [x] Documentation completed
- [x] Customer templates deferred (out of scope)
