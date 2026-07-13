# Metadata Runtime Contract

## Purpose

This document is the canonical runtime contract for NovaCRM metadata values. It applies to web forms, API intake, lead conversion, future entity integrations, and future validation/search/reporting phases.

The approved runtime flow is:

Metadata Definition -> MetadataFormResolver -> MetadataFormValuePresenter -> Runtime Blade Partials -> MetadataValueStorageService -> Entity JSON Storage

## Storage Structure

Dynamic values are stored on each metadata-enabled entity in its `custom_fields` JSON column.

The persisted shape is a flat JSON object:

```json
{
  "field_key": "value"
}
```

Rules:

- The JSON object is keyed by `MetadataFieldDefinition.key`.
- Field labels are never used as persisted identifiers.
- Field definition IDs are not embedded in `custom_fields`.
- Metadata such as label, type, sensitivity, options, and future validation rules is resolved from metadata definitions.
- A missing or empty metadata object may be stored as `null`.
- Unknown keys are ignored by default.
- Unknown keys may be stored only when a caller explicitly requests legacy compatibility, such as Lead Intake API compatibility.

## Stable Identifiers

`MetadataFieldDefinition.key` is the stable runtime identifier.

- Keys must remain stable after publish.
- Labels may change without changing stored values.
- Option-backed fields store option values, not option labels.
- Future APIs, validation, search, reports, automations, and imports must address metadata values by key.

## Null And Clear Semantics

The runtime distinguishes omitted values from explicit clears.

- Not submitted: leave the existing value unchanged.
- Submitted `null`: explicitly clear the field and remove the key from `custom_fields`.
- Submitted empty string: normalize to `null`, then clear the field.
- Submitted empty array for multi-select: explicitly clear the field and remove the key from `custom_fields`.
- Deleted value: represented by key removal from `custom_fields`.
- Explicit clear: a submitted value that normalizes to `null` or an empty multi-select array.

If clearing the last remaining key, the entire `custom_fields` column may be stored as `null`.

## Boolean Contract

Boolean metadata values are stored as JSON booleans.

- Truthy submitted values: `true`, `1`, `"1"`, `"true"`, `"yes"`, `"on"`.
- False submitted values: `false`, `0`, `"0"`, and any non-truthy scalar.
- Unchecked web checkboxes must submit an explicit false value.
- `false` is a real stored value and must not be treated as an empty value or clear.

## Multi-Select Contract

Multi-select values are stored as JSON arrays of option values.

- Non-empty array: store the filtered array of submitted option values.
- Empty array: clear the field and remove the key.
- `null`: clear the field and remove the key.
- Empty string entries inside an array are removed before storage.
- Option labels are resolved at render/export/report time from metadata options.

`[]` is a submitted clear signal, not the canonical persisted value.

## Date And Time Contract

Canonical storage:

- Date: `YYYY-MM-DD`.
- DateTime: ISO-8601 / Atom string.
- Time: `HH:MM:SS`.

Browser inputs:

- `date` inputs submit `YYYY-MM-DD`.
- `datetime-local` inputs submit a local timestamp without timezone.
- `time` inputs submit `HH:MM`.

Runtime storage normalizes date/time strings into the canonical storage shape where possible. Future validation must define accepted input formats and timezone policy before rejecting invalid date/time values.

Timezone handling:

- Stored DateTime values should be serialized with timezone offset when parseable.
- Browser-local DateTime input must be normalized before storage.
- Presentation may format values for HTML inputs without changing the stored canonical value.

## Numeric Contract

Numeric metadata values are normalized by field type.

- `number`, `user`, and `team`: integer when numeric.
- `decimal`, `currency`, and `percentage`: float when numeric.
- Non-numeric values are not rejected by storage; future validation must reject invalid values before merge.
- Currency precision and decimal scale are validation concerns, not storage concerns.

## String Contract

String-like values include `text`, `textarea`, `email`, `url`, `phone`, `select`, and `radio`.

- String scalar values are trimmed before storage.
- Empty strings normalize to `null` and clear the field.
- Unicode text is preserved.
- Storage does not validate email, URL, phone, option membership, length, or format.
- Future validation must enforce format and length constraints before storage.

## Unknown Keys

Default behavior:

- Unknown keys are ignored.
- Ignored keys are reported in the storage result.
- Ignored keys do not mutate existing values.

Legacy compatibility behavior:

- A caller may explicitly allow unknown keys.
- This mode is for compatibility-only channels such as existing Lead Intake API behavior.
- New runtime integrations should not enable unknown-key writes by default.

## Inactive Fields

Only active metadata definitions are writable by default.

- Draft, published-but-not-active, inactive, and archived definitions are ignored by default.
- Existing stored values for inactive keys are preserved if omitted.
- Future administrative migration tools may define separate behavior.

## Sensitive Fields

Sensitive metadata values are not encrypted in this phase.

Runtime behavior:

- Detail display masks sensitive values.
- Edit forms may render values unless future permission rules hide or make them read-only.
- API visibility and export visibility are controlled by metadata flags, but enforcement is reserved for future API/export phases.
- Audit-ready diffs may contain old/new values; sensitive audit masking is a future policy decision.

Future phases must define:

- Who may view sensitive values.
- Whether sensitive values are omitted or masked in APIs.
- Whether sensitive values are included in audit payloads.
- Whether sensitive fields can be edited without viewing the current value.

## Audit-Ready Diff Contract

Metadata storage returns a diff shape for changed keys:

```json
{
  "field_key": {
    "field_id": 123,
    "old": "previous",
    "new": "next"
  }
}
```

Rules:

- `field_id` may be `null` only for explicit legacy unknown-key mode.
- `old` is the previous stored value or `null`.
- `new` is the normalized stored value or `null` for a clear.
- Omitted values do not appear in the diff.

## Non-Responsibilities Of Storage

`MetadataValueStorageService` does not:

- Enforce dynamic validation rules.
- Enforce required fields.
- Enforce uniqueness.
- Enforce option membership.
- Update search/report projections.
- Emit timeline or automation events.
- Execute visibility, display, permission, formula, or relationship rules.

Those responsibilities belong to future phases that must use this runtime contract.
