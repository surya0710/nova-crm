# P3 Metadata Platform - Phase 4.5 Architecture Impact Report

## Phase

Phase 4.5 - Metadata Platform Hardening & Phase 5 Readiness

## What Changed?

- Added a single metadata orchestration service for entity form integration:
  - `MetadataEntityFormService`
  - Coordinates `MetadataFormResolver`
  - Exposes `MetadataFormValuePresenter`
  - Coordinates `MetadataValueStorageService`
- Removed duplicated metadata plumbing from:
  - Lead controller
  - Customer controller
  - Opportunity controller
  - Organization settings controller
- Unified production metadata writes so `custom_fields` persistence flows through `MetadataValueStorageService`.
- Updated Lead Intake API metadata persistence to use the metadata storage path while preserving explicit legacy unknown-key compatibility.
- Updated lead conversion metadata copying to use metadata storage and target-entity active definitions instead of direct JSON copying.
- Hardened metadata value normalization for:
  - Empty string clears
  - Empty multi-select clears
  - Boolean false preservation
  - Date normalization
  - DateTime normalization
  - Time normalization
  - Trimmed scalar strings
- Improved browser input repopulation for date, datetime, and time metadata fields.
- Cleaned up Organization settings metadata UX by removing duplicate read-only metadata rendering from the edit tab.
- Added canonical runtime documentation:
  - `docs/METADATA_RUNTIME_CONTRACT.md`
- Added focused tests for Phase 4.5 edge cases and write-path parity.

## Technical Debt Removed

- Controller-level metadata orchestration duplication was replaced by `MetadataEntityFormService`.
- Web controllers no longer each own their own metadata extraction and persistence helpers.
- Lead Intake API no longer writes dynamic values directly into `custom_fields`.
- Lead conversion no longer copies lead metadata JSON directly onto customers.
- Organization settings no longer renders duplicate edit/detail metadata blocks on the same tab.

## Runtime Contract Frozen

The runtime contract is now documented in `docs/METADATA_RUNTIME_CONTRACT.md`.

The contract defines:

- Flat `custom_fields` JSON object.
- Stable field-key addressing.
- Unknown-key behavior.
- Omitted vs null vs empty string vs empty array behavior.
- Explicit clear behavior.
- Boolean false persistence.
- Multi-select clear and storage behavior.
- Date, DateTime, and Time canonical storage formats.
- Numeric normalization rules.
- String trimming and empty-string clearing.
- Sensitive display strategy.
- Audit-ready diff shape.
- Non-responsibilities of storage.

## Architecture Review

The approved Phases 1-4 runtime architecture remains intact:

Metadata Definition -> MetadataFormResolver -> MetadataFormValuePresenter -> Shared Runtime Blade Partials -> MetadataValueStorageService -> Entity JSON Storage

Phase 4.5 did not introduce traits, generic controller inheritance, repositories, validation engines, search projections, reporting, automation, AI, or import/export behavior.

The new `MetadataEntityFormService` is an orchestration layer only. It does not contain entity domain logic. Entity controllers remain responsible for their own workflows, policies, static validation, redirects, and domain side effects.

## Metadata Write Path Review

Production metadata value persistence now flows through `MetadataValueStorageService`.

Current production write channels:

- Web forms: routed through `MetadataEntityFormService::persistFromRequest()`.
- Lead Intake API: routed through `MetadataEntityFormService::persistValues()` with explicit legacy unknown-key compatibility.
- Lead conversion: routed through `MetadataEntityFormService::persistValues()` against target customer/opportunity entities.

Direct `custom_fields` writes remain only in model casts, request validation definitions, tests/factories, and `MetadataValueStorageService` itself.

## Sensitive Field Strategy

Phase 4.5 does not implement encryption or full field-level permissions.

Current behavior:

- Sensitive values are masked in runtime detail display.
- Sensitive values may still appear in edit forms.
- API/export/audit masking remains a future policy decision.

Future phases should define:

- Sensitive field read permission.
- Sensitive field edit-without-view behavior.
- API visibility behavior.
- Audit visibility behavior.
- Export behavior.

## Performance Observations

Current metadata reads are acceptable for realistic first-wave usage.

- Resolver queries are scoped by organization, entity type, context, and active status.
- Groups and options are eager-loaded by the resolver.
- Storage performs one active-definition lookup per metadata merge.
- Entity show/edit pages perform bounded metadata work per request.

Potential future optimization:

- Request-level cache for active definitions by `(organization_id, entity_type)`.
- Shared definition query helper if Phase 5 needs repeated lookups during validation and storage in the same request.

No optimization was necessary in Phase 4.5.

## Tests Added Or Expanded

Coverage was expanded for:

- Empty string clears.
- Explicit null clearing.
- Empty multi-select clearing.
- Boolean false storage.
- Date normalization.
- DateTime normalization.
- Time normalization.
- Browser input formatting for temporal fields.
- Sensitive field masking.
- Lead Intake API storage normalization.
- Lead Intake API legacy unknown-key compatibility.
- Lead conversion metadata copying through target entity definitions.
- Existing entity dynamic-form regressions.

## Remaining Risks

- Dynamic validation is still not implemented.
- `validation_rules` JSON schema still needs to be designed as part of Phase 5.
- Option membership is not enforced by storage.
- Required fields are not enforced by storage.
- Unique metadata values are not enforced.
- Field-level permissions remain architectural scaffolding, not runtime enforcement.
- User/team field rendering is still basic and should be revisited before relying on those field types heavily.

These are Phase 5 or later concerns and were intentionally not implemented in Phase 4.5.

## Did Any Architectural Assumptions Change?

No major architecture assumptions changed.

The Hybrid Metadata Model remains valid.
Runtime metadata remains organization-owned.
JSON remains the canonical current-value store.
Dynamic validation remains a separate Phase 5 capability.
Search, reporting, dashboarding, timeline, automation, AI, import/export, and API hardening remain separate future capabilities.

One implementation detail was clarified:

- Metadata write ingress should be centralized before validation is introduced. `MetadataEntityFormService` is now the single orchestration point for entity metadata interactions.

## Is Phase 5 Still Valid?

Yes.

Phase 5 - Dynamic Validation remains valid and can now build on a more stable foundation.

Phase 5 should:

- Define the `validation_rules` JSON schema.
- Compile metadata definitions into Laravel validation rules.
- Enforce required fields.
- Enforce type rules.
- Enforce option membership.
- Normalize and validate date/time formats.
- Attach validation errors to `custom_fields.{field_key}`.
- Reuse the established metadata orchestration and storage services.

Phase 5 should not redesign the storage contract or bypass `MetadataValueStorageService`.

## CTO Recommendation

Proceed to P3 Phase 5 - Dynamic Validation after reviewing Phase 4.5.

There are no remaining blockers in the Phases 1-4 runtime foundation that require architectural redesign before Phase 5.
