# Metadata Validation Contract

## Purpose

`MetadataValidationService` is the single authoritative entry point for dynamic metadata validation across NovaCRM.

Any channel that accepts metadata values must route validation through this service directly or through an orchestration service that delegates to it. This includes web forms, APIs, imports, automation, future AI-assisted data entry, and internal service workflows.

## Runtime Boundary

Validation happens before metadata values are written to an entity.

`MetadataValueStorageService` remains the canonical write and normalization path. It does not own validation rules. Validation confirms whether submitted values are acceptable; storage confirms how accepted values are normalized and persisted.

## Orchestration

`MetadataEntityFormService` is the preferred web and application-layer entry point.

It resolves active fields for the entity/context, extracts submitted form values using `MetadataFormValuePresenter`, delegates validation to `MetadataValidationService`, and only then passes accepted values to `MetadataValueStorageService`.

Controllers should not build dynamic validation rules locally. They should ask `MetadataEntityFormService` for validated metadata values before saving static entity data, then persist those validated values after the entity exists.

## Supported Validation Semantics

The service validates:

- Required fields, including required fields omitted from a create request.
- Existing values on update, so omitted required fields remain valid when the record already has a non-empty value.
- Type rules for scalar, numeric, boolean, temporal, select, radio, and multi-select fields.
- Option membership for select, radio, and multi-select fields.
- Unique metadata values scoped to organization and entity type.
- Supported `validation_rules` JSON keys.

Boolean `false`, `0`, and `"0"` are valid non-empty values for required boolean fields.

Empty strings and `null` clear optional scalar fields. Empty multi-select arrays clear optional multi-select fields.

## Unknown Keys

Unknown metadata keys are ignored by default for rendered web forms because only resolved fields are extracted from form payloads.

Non-form channels may explicitly pass `allowUnknown: true` for legacy compatibility. In that mode, known active fields are still validated, while unknown keys are allowed through to the storage service.

## Required Enforcement

Most user input channels enforce required dynamic fields.

Internal copy workflows, such as lead conversion, may disable required enforcement when they are copying only intersecting metadata values into a target entity. This prevents unrelated required target fields from blocking conversion.

## Supported `validation_rules` Keys

Metadata definitions may use these JSON validation rule keys:

- `min`
- `max`
- `regex`
- `before`
- `after`
- `decimal_places`
- `allowed_values`

Unsupported keys are rejected when metadata definitions are created or updated.

## Error Shape

Validation errors use the same input path as rendered forms and APIs:

`custom_fields.{metadata_key}`

This keeps Blade error display, JSON API validation responses, and future channel integrations aligned.
