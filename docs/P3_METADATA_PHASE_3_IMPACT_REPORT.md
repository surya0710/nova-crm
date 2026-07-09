# P3 Metadata Platform - Phase 3 Architecture Impact Report

## Phase

Phase 3 - Runtime Value Storage

## What Changed?

- Added JSON metadata value storage to first-wave entities that did not already have it:
  - Opportunities
  - Organizations
- Preserved existing Lead and Customer `custom_fields` storage.
- Added casts/fillable support for Opportunity and Organization metadata values.
- Added a storage-only metadata value service that:
  - Resolves active organization-owned field definitions by entity.
  - Merges submitted values into existing `custom_fields`.
  - Preserves omitted values.
  - Clears values only when a key is explicitly submitted with `null`.
  - Ignores unknown keys by default.
  - Allows legacy unknown-key storage only when explicitly requested.
  - Normalizes values for storage by field type.
  - Returns audit-ready old/new diffs.
- Added storage-focused tests for:
  - Active metadata value writes.
  - Legacy custom field preservation.
  - Unknown-key compatibility mode.
  - Null-clearing semantics.
  - Inactive field ignoring.
  - Opportunity and Organization metadata storage.
  - Existing Lead Intake API compatibility.
  - Existing Lead Conversion compatibility.

## Explicitly Not Implemented

Per CTO instruction, Phase 3 did not implement:

- Dynamic Forms
- Dynamic Validation Engine
- Search
- Reports
- Dashboards
- Automation
- Timeline UI
- AI
- Import/Export enhancements

The storage service intentionally does not render fields, enforce dynamic validation rules, update search projections, emit automation events, or create timeline UI entries.

## Canonical JSON Value Contract

Dynamic values are stored on supported entity records in the entity `custom_fields` JSON object.

This contract is now the foundation for future validation, APIs, reports, AI, search, timeline, automation, and import/export phases.

### Field Addressing

- Values are stored by stable field key, not by display label.
- Field keys come from organization-owned `MetadataFieldDefinition.key`.
- Field labels may change without changing stored JSON keys.
- Field definition IDs are returned in storage diffs where available, but the canonical JSON object remains keyed by stable field key for API and legacy compatibility.
- Unknown keys are ignored by default.
- Unknown keys may be written only when a caller explicitly enables legacy compatibility mode.

Example:

```json
{
  "visa_type": "student",
  "ielts_score": 7.5,
  "destination_country": "Canada"
}
```

### Canonical Value Shape

- `custom_fields` is a flat JSON object of `field_key => value`.
- Nested field objects are not used in Phase 3.
- Metadata such as labels, types, permissions, and options is resolved from `MetadataFieldDefinition`, not duplicated into every record.
- Audit-ready diffs may include `field_id`, `old`, and `new`, but that diff structure is not the persisted entity value shape.

### Null And Omitted Values

- Omitted keys mean "leave existing value unchanged."
- Submitted `null` means "clear this field value."
- Cleared values are removed from the JSON object.
- If no dynamic values remain, `custom_fields` may be stored as `null`.
- Future forms, APIs, imports, automations, and validation must preserve this distinction between omitted and explicit `null`.

### Multi-Select Representation

- Multi-select values are always stored as JSON arrays.
- Empty, null, or blank entries are removed during storage normalization.
- Option values, not option labels, should be stored.
- Option labels are resolved from metadata at render/export/report time.

Example:

```json
{
  "preferred_countries": ["canada", "australia"]
}
```

### Date And Time Serialization

- Date values are stored as `YYYY-MM-DD` strings.
- DateTime values are stored as ISO-8601 / Atom strings when passed as date objects.
- Time values are stored as `HH:MM:SS` strings when passed as date objects.
- Future validation must normalize user input before storage if accepting localized date/time formats.
- Future APIs should expose the stored canonical representation unless an endpoint explicitly documents presentation formatting.

### Scalar Type Normalization

- `number`, `user`, and `team` values are normalized to integers when numeric.
- `decimal`, `currency`, and `percentage` values are normalized to floats when numeric.
- `boolean` values are normalized to booleans.
- Text-like values are stored as strings.
- Legacy unknown values keep their submitted JSON-compatible shape when legacy compatibility mode is explicitly enabled.

### Lifecycle Write Rules

- Only active field definitions are writable by default.
- Draft, published-but-not-active, inactive, and archived fields are ignored by default by the storage service.
- Future administrative migration tools may choose to write non-active fields, but that must be an explicit separate capability.

## Which Future Phases Are Now Enabled?

- Phase 4 - Dynamic Forms can now write to a stable value storage service instead of directly mutating JSON.
- Phase 5 - Dynamic Validation can validate before calling the storage service, keeping storage and validation separate.
- Future Timeline work can consume the old/new diff returned by the storage service.
- Future Automation work can consume the same old/new diff without changing the storage contract.
- Future Search and Reporting can build projections from canonical `custom_fields` values.
- Future API hardening can use the same storage service for metadata-aware writes.

## Did Any Architectural Assumptions Change?

- No major architecture assumptions changed.
- The Hybrid Metadata Model remains valid.
- JSON remains the canonical current-value store for dynamic fields.
- Typed search/report projections remain deferred.
- Dynamic validation remains a separate future phase.
- Existing Lead and Customer custom field behavior remains backward compatible.

One implementation detail was clarified:

- Runtime storage should write only active metadata fields by default. Draft, published-but-not-active, inactive, and archived fields are not writable through the metadata storage service unless future admin migration tools explicitly support them.

## Is The Next Phase Still Valid As Designed?

Yes.

Phase 4 - Dynamic Forms remains valid as designed, but it should consume the Phase 3 storage service rather than writing directly to entity JSON.

Phase 4 should focus only on rendering and submitting dynamic fields in first-wave entity forms. Dynamic validation should remain a separate phase unless CTO approval changes the boundary.

## CTO Recommendation

Proceed to Phase 4 after reviewing Phase 3.

Phase 4 should include:

- Metadata field rendering for first-wave entity create/edit/detail surfaces.
- Server-side use of the storage service for persistence.
- Permission-aware field visibility only if scoped to display/write gating, not the full validation engine.
- No search/report/dashboard/automation/timeline/AI behavior.
- Tests proving dynamic form submissions persist through `MetadataValueStorageService` and preserve omitted values.
