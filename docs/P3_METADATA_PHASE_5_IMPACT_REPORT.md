# P3 Metadata Phase 5 Impact Report

## Summary

Phase 5 introduces dynamic metadata validation as a pre-storage gate.

The central architectural outcome is that `MetadataValidationService` is now the only authoritative validation entry point for metadata values. Web forms, APIs, and internal metadata workflows route through this service directly or through `MetadataEntityFormService`.

## Key Changes

- Added `MetadataValidationService` for required, type, option, uniqueness, and JSON rule validation.
- Extended `MetadataEntityFormService` to validate request and raw metadata payloads before writing values.
- Updated Lead, Customer, Opportunity, and Organization web flows to validate dynamic metadata before saving static entity data.
- Updated Lead Intake API metadata handling to validate known active fields while preserving explicit legacy unknown-key compatibility.
- Updated Lead conversion metadata copying to validate copied target values without requiring unrelated target metadata fields.
- Added metadata definition validation for supported `validation_rules` JSON schema.
- Documented the runtime validation contract in `docs/METADATA_VALIDATION_CONTRACT.md`.

## Architectural Decisions

`MetadataValueStorageService` remains normalization-only and continues to be the single write path.

`MetadataValidationService` owns accept/reject decisions. This keeps validation logic out of controllers, form requests, storage, imports, automation, and future AI entry points.

`MetadataEntityFormService` remains the application-layer facade for web and common service flows. Controllers request validated metadata values from it and persist those validated values after the target model exists.

## Behavior

Required metadata fields fail when submitted empty or omitted on create.

On update, omitted required metadata remains valid when the record already has a non-empty value. Submitted empty required values still fail.

Select, radio, and multi-select values must match active metadata options.

Unknown web form keys are ignored through the existing rendered-field extraction path. Lead Intake API retains its legacy `allowUnknown` behavior, but known active fields are still validated.

## Tests Added

- `MetadataValidationServiceTest`
- Additional `LeadDynamicMetadataFormTest` coverage for missing required fields, invalid options, and optional clears.
- Additional `LeadIntakeApiTest` coverage for required metadata validation failures.
- Additional `MetadataFieldDefinitionTest` coverage for unsupported `validation_rules` keys.

## Remaining Risks

Imports, automation, and future AI-assisted entry points are not implemented yet. Their metadata validation contract is now defined, but those channels must explicitly call `MetadataValidationService` or `MetadataEntityFormService` when introduced.

Uniqueness validation currently uses JSON path queries against entity `custom_fields`. This is acceptable for Phase 5 scale, but high-volume installations may eventually need indexed generated columns or a dedicated metadata value index.

## Phase 6 Readiness

The metadata platform now has separate authoritative boundaries for rendering, validation, and storage.

Phase 6 can build search, reporting, automation, or import features on this foundation without duplicating metadata validation rules.
