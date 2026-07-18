# Marketing Attribution Runtime Contract

## Status

- Phase: P7B.F (Foundation Freeze)
- State: **Frozen**
- Companion documents: `docs/MARKETING_ATTRIBUTION_CONTRACT.md`, `docs/MARKETING_CONVERSION_CONTRACT.md`
- Implementation reference: Phase 7B.4 (Identity Resolution & Lead Attribution)

## Purpose

This contract freezes how anonymous marketing identity becomes a durable relationship to CRM entities. Attribution is a **relationship layer**, not a mutation of CRM tables with marketing metadata.

`MarketingAttributionService` is the **single write authority** for `marketing_attributions`.

## Attribution Authority

Rules:

- The `MarketingAttribution` row is the only authoritative link from a Lead (and later Customer / Opportunity) to marketing identity.
- Marketing metadata (channel, UTMs, click IDs, touch history) lives on touches / classification — **not** copied onto `leads`, `customers`, or `opportunities`.
- Legacy `leads.source` remains a coarse compatibility field when synced by a future phase; it is never the source of truth.
- No feature may infer attribution from notes, audit logs, or provider payloads directly. All such data flows into the platform first.

## Single Write Authority

| Concern | Authority |
| --- | --- |
| Attribute visitor → lead | `MarketingAttributionService` |
| Propagate lead → customer / opportunity | `MarketingAttributionService::propagateToConversion` |
| Claim visitor `organization_id` | `MarketingAttributionService` (inside attribution transaction) |
| Read primary attribution | `MarketingAttributionService` finders |

CRM services (`LeadService`, `LeadConversionService`) call the attribution service additively. Controllers never write attribution rows.

## Attribution Lifecycle

```
Anonymous Visitor (organization_id null)
        ↓ page views / touches
Marketing Sessions + Touches
        ↓ lead create with resolvable visitor
MarketingAttributionService::attributeLead
        ↓ claim visitor ownership
MarketingAttribution (first_touch, is_primary)
        ↓ Lead
        ↓ lead convert
propagateToConversion
        ↓ same row gains customer_id / opportunity_id
Customer + Opportunity (relationship only)
```

Stage rules:

1. **Visitor → Lead** — create one primary `first_touch` attribution when a visitor can be resolved.
2. **Lead → Customer / Opportunity** — update the **same** attribution row with FKs. No second attribution record.
3. **No visitor / unknown visitor** — zero attribution rows; CRM behavior identical to pre-platform.
4. **Later visits** by the same person do not modify an existing Lead's attribution.

## First-Touch Model

| Field | Contract |
| --- | --- |
| `attribution_model` | Always `first_touch` in the frozen runtime (`config('marketing.attribution.default_model')`) |
| Session reference | Session of the visitor's **earliest touch** (`occurred_at`), not necessarily the session cookie at submission |
| Fallback when no touches | Supplied `session_uuid` (if owned by visitor) → earliest session → null session |
| `is_primary` | `true` for the authoritative row |

One visitor → one primary lead attribution. A second lead submitted with the same visitor UUID does not receive attribution.

## Identity Graph

```
MarketingVisitor
    └── MarketingSession[]
            └── MarketingTouch[]     ← classification lives here
    └── MarketingAttribution[]       ← relationship to CRM
            ├── Lead (required at create)
            ├── Customer (nullable, set on convert)
            └── Opportunity (nullable, set on convert)
```

Signal resolution order for `attributeLead`:

1. Explicit `visitor_uuid` / `session_uuid` in call signals (API payload).
2. Request attributes set by tracking middleware.
3. Tracking cookies.
4. Missing / unknown → no attribution.

## Tenant Ownership

| Rule | Behavior |
| --- | --- |
| Attribution row | Always has `organization_id` = lead's organization; uses `BelongsToOrganization` |
| Visitor claim | Unowned visitor (`organization_id` null) may be claimed by the lead's organization |
| Cross-tenant | Visitor already owned by another organization → attribution refused |
| Propagation | Attribution, customer, and opportunity must share the lead's `organization_id` |

## Propagation Rules

- Propagation mutates FKs on the existing attribution row inside the CRM conversion transaction.
- Conversion without prior attribution is a **no-op** for Marketing and must not fail CRM conversion.
- Attribution never outlives its Lead (cascade / delete semantics follow the Lead).

## Duplicate Prevention

| Guarantee | Mechanism |
| --- | --- |
| One primary attribution per lead | Unique index on `lead_id`; service returns existing row |
| One primary attribution per visitor | Service-enforced; second lead with same visitor gets no attribution |
| Idempotent re-call | `attributeLead` for an already-attributed lead returns the existing row |

Primary-per-visitor uniqueness stays in the service (not a DB unique on `(visitor_id, is_primary)`) so future non-primary model rows can coexist.

## Future Attribution Models

Config today:

```
supported_models: ['first_touch']
default_model: first_touch
```

Designed for additive rows without schema change:

| Model | Status |
| --- | --- |
| `first_touch` | Implemented (frozen) |
| `last_touch` | Future |
| `linear` | Future |
| `position_based` | Future |
| `time_decay` | Future |

Future models:

- Consume immutable touch history as a pure read.
- Introduce no new write semantics on touches.
- May add non-primary attribution rows; the primary row remains the default reporting authority unless a consumer explicitly selects another model.
- Require contract revision before becoming default.

## CRM Integration Points (Frozen Hooks)

| CRM operation | Marketing hook |
| --- | --- |
| `LeadService::create` / `createFromApi` | `attributeLead` after lead persist |
| `LeadConversionService::convert` | `propagateToConversion` inside conversion transaction |

API intake accepts optional `visitor_uuid` / `session_uuid` (additive). Web lead creation falls back to tracking cookies when present.

## Extension Rules

Allowed without architectural change:

- Additional finder methods on `MarketingAttributionService`.
- Future model rows behind config + contract revision.
- Enrichment of structural campaign FKs on attribution (when those columns exist) by provider adapters — never overwriting frozen touch snapshots.

Prohibited:

- Writing marketing UTM/channel/click ID columns onto CRM entity tables.
- Controllers or providers writing `marketing_attributions` directly.
- Overwriting an existing primary attribution.
- Cross-tenant visitor claims.
- Blocking lead creation when attribution fails.

## Non-Responsibilities

- Conversion event emission (see Conversion Contract).
- Historical backfill orchestration (see Backfill Contract).
- Multi-touch credit allocation algorithms.
- Provider offline conversion upload.
