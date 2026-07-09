# P3 Metadata Platform - Phase 2 Architecture Impact Report

## Phase

Phase 2 - Industry Template Activation

## What Changed?

- Added a metadata blueprint activation service that converts copied Industry Template `field_blueprints` into organization-owned metadata definitions.
- Integrated activation into the platform organization onboarding flow immediately after template settings are copied to the organization.
- Added an existing-organization backfill action from the tenant Metadata Fields screen.
- Added idempotency handling so repeated activation does not duplicate fields.
- Added conflict reporting when an organization already owns a field with the same entity and key.
- Added provenance on activated fields:
  - Source: `industry_template`
  - Source type: `IndustryTemplateVersion`
  - Source identifier: applied template version ID
- Added blueprint activation version snapshots using the `blueprint_activated` event.
- Added option materialization for select, multi-select, and radio blueprints.
- Added group materialization from blueprint group labels.
- Hardened Industry Template publish validation for P3 field blueprint contracts:
  - Supported entity validation
  - Supported field type validation
  - Required field labels
  - Required options for option-backed fields
- Added tenant UI affordance to activate copied template fields for organizations that already have `settings.field_blueprints`.
- Suppressed tenant audit model events during platform-driven blueprint materialization to avoid writing platform-user IDs into tenant audit logs. Provenance remains captured through field source metadata, field versions, and the existing platform template application audit trail.

## Which Future Phases Are Now Enabled?

- Phase 3 - Runtime Value Storage can now resolve active, organization-owned metadata fields that originated from Industry Templates.
- Phase 4 - Dynamic Forms can now render tenant fields seeded by Industry Templates, not just manually created fields.
- Phase 5 - Validation can now consume blueprint-derived required flags, type metadata, options, and validation rules.
- Search and reporting phases can now use blueprint-derived search/report flags.
- API phases can now expose template-seeded runtime fields through the same metadata catalog as manually created fields.
- AI phases can now discover industry-specific fields after organization onboarding without querying platform template records.

## Did Any Architectural Assumptions Change?

- No major architecture assumptions changed.
- The copy-on-apply rule from P2 remains intact.
- Platform still owns only Field Blueprints, never runtime metadata.
- Runtime metadata remains organization-owned after activation.
- Template version records are provenance, not runtime dependencies.
- The Hybrid Metadata Model remains valid.
- Runtime value persistence remains deferred to Phase 3.

One implementation detail was clarified:

- Blueprint materialization is a system/provenance operation, not a normal tenant user edit. It should not emit tenant model audit entries during platform onboarding because the authenticated actor is a platform user. The durable audit/provenance path is:
  - Platform template application audit log
  - Organization template application summary
  - Metadata field source fields
  - Metadata field version snapshot

## Is The Next Phase Still Valid As Designed?

Yes.

Phase 3 - Runtime Value Storage is still valid as designed. The system now has stable organization-owned metadata definitions that can be used to validate and persist dynamic values on first-wave entities.

Phase 3 should not redesign template activation. It should consume the activated metadata catalog and focus on value persistence, value normalization, update flow, audit diffs, and compatibility with existing lead/customer `custom_fields`.

## CTO Recommendation

Proceed to Phase 3 after reviewing Phase 2.

Phase 3 should include:

- Runtime value handling service for dynamic fields.
- Compatibility bridge for existing Lead and Customer `custom_fields`.
- Dynamic value persistence for first-wave entities where storage is available.
- Additive storage support for first-wave entities that do not yet have dynamic value storage.
- Value normalization by field type.
- Safe merge/update semantics that preserve hidden or omitted values.
- Audit-ready old/new dynamic value diffs.
- Tests for existing API custom fields, activated template fields, tenant isolation, and value preservation.
