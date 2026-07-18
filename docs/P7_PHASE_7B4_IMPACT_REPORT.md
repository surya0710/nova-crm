# P7 Marketing Attribution Platform - Phase 7B.4 Impact Report

## Phase

Phase 7B.4 - Identity Resolution & Lead Attribution

## What Changed?

- Added additive, reversible migration `marketing_attributions` linking anonymous marketing identity to CRM entities without copying marketing metadata into CRM tables.
- Added `MarketingAttribution` model using `BelongsToOrganization` (tenant-owned from the moment of attribution).
- Added `MarketingAttributionService` as the single write authority for attribution: resolve visitor ownership, create first-touch primary attribution, retrieve attribution, propagate through conversion, and enforce duplicate / cross-tenant rules.
- Hooked attribution into existing CRM services only (no controller rewrite beyond existing service delegation):
  - `LeadService::create` / `createFromApi` call `attributeLead` after the lead is persisted.
  - `LeadConversionService::convert` calls `propagateToConversion` inside the existing conversion transaction.
- API intake accepts optional `visitor_uuid` / `session_uuid` (additive to `StoreApiLeadRequest`). Web lead creation falls back to tracking cookies when present.
- Added `config/marketing.php` `attribution` section (`default_model`, `supported_models`) so future models need no schema change.
- Added relationships: `Lead`/`Customer`/`Opportunity` → `marketingAttribution()`, `MarketingVisitor` → `attributions()`.
- Added `tests/Feature/MarketingAttributionTest.php` (14 tests, 58 assertions).

## Architecture

```
Lead create (web / API)
        ↓
LeadService
        ↓
MarketingAttributionService::attributeLead
        ↓ (resolve visitor from signals / cookies)
MarketingVisitor ownership claimed (organization_id)
        ↓
MarketingAttribution (first_touch, is_primary)
        ↓
Lead  ←── relationship only (no UTM/channel/click ID columns on leads)

Lead convert
        ↓
LeadConversionService
        ↓
MarketingAttributionService::propagateToConversion
        ↓
same MarketingAttribution row updated with customer_id / opportunity_id
```

- Marketing remains the source of truth for channel, UTMs, click IDs, and touch history.
- CRM references Marketing via `marketing_attributions`. No marketing metadata is duplicated onto `leads`, `customers`, or `opportunities`.
- Tracking runtime (7B.1–7B.3) is unchanged. Controllers remain thin. Middleware remains orchestration only.

## Identity Resolution Model

```
Marketing Visitor
        ↓
Marketing Sessions
        ↓
Marketing Touches   ← classification lives here (7B.3)
        ↓
Marketing Attribution  ← relationship layer (this phase)
        ↓
Lead → Customer → Opportunity
```

- Visitors remain anonymous (`organization_id` null) until attribution.
- At attribution time the visitor is claimed by the lead's organization.
- Cross-tenant claim is refused: a visitor already owned by org B cannot attribute a lead in org A.
- Signals: explicit `visitor_uuid` / `session_uuid`, else request attributes, else tracking cookies. Missing / unknown visitor → no attribution; lead creation continues unchanged.

## Attribution Lifecycle

1. **Visitor → Lead** — create one primary `first_touch` attribution when a visitor can be resolved.
2. **Lead → Customer / Opportunity** — update the same attribution row with `customer_id` and optional `opportunity_id`. No second attribution record.
3. **No visitor** — zero attribution rows; CRM behavior identical to pre-7B.4.

## First-Touch Attribution

- `attribution_model` is always `first_touch` in this phase (from config).
- Session reference is the session of the visitor's earliest touch (`occurred_at`), not the session cookie at submission. That preserves true first-touch even when the lead submits in a later session.
- Fallback order when no touches exist: supplied `session_uuid` (if owned by the visitor) → earliest session → null session.

## Propagation Flow

- Propagation mutates FKs on the existing attribution row inside the conversion transaction.
- Tenant checks ensure attribution, customer, and opportunity share the lead's `organization_id`.
- Conversion without prior attribution is a no-op for Marketing and does not fail CRM conversion.

## Duplicate Prevention

- One primary attribution per lead (`unique(lead_id)`).
- One primary attribution per visitor (service-enforced): a second lead submitted with the same visitor UUID does not receive attribution.
- Re-calling `attributeLead` for an already-attributed lead returns the existing row.

## Tenant Isolation

- `MarketingAttribution` uses `BelongsToOrganization` + `OrganizationScope`.
- Visitor ownership is resolved only to the lead's organization, never across tenants.
- Indexes: `mkt_attr_org_visitor_idx`, `mkt_attr_org_customer_idx`, `mkt_attr_org_model_idx`, `mkt_attr_visitor_primary_idx`, `mkt_attr_lead_unique`.

## Testing Summary

- `php artisan test --filter=Marketing` — 69 passed (269 assertions): 7B.1–7B.3 suites fully green plus 14 new attribution tests covering:
  - Visitor → Lead first-touch attribution
  - No visitor / unknown visitor → no attribution
  - Duplicate prevention (same lead and same visitor)
  - First-touch earliest-session resolution
  - Cookie and API signal paths
  - Conversion propagation to customer and opportunity
  - Conversion without attribution
  - Cross-tenant protection and organization scoping
  - Tracking runtime and CRM regression without visitors
- `php artisan test` (full suite) — 516 passed (1678 assertions), 0 failures. Baseline 502 fully green plus 14 new tests. CRM, Metadata, Revenue, and prior Marketing phases unaffected.

## Performance Considerations

- Attribution on lead create: one visitor UUID lookup, one primary-attribution existence check, one earliest-touch query (indexed via session→visitor), one attribution insert, one optional visitor ownership update — all inside a short transaction for the visitor-claim path.
- Propagation: one lead attribution lookup + one update; no additional touch scans.
- No caching introduced. Reporting joins remain deferred to later phases.

## Future Attribution Models

The `attribution_model` string column and `is_primary` flag are designed so later phases can add `last_touch`, `linear`, `position_based`, and `time_decay` as additional rows without schema changes. Primary-per-visitor uniqueness stays in the service (not a DB unique on `(visitor_id, is_primary)`) so multiple non-primary model rows can coexist for one visitor.

## Explicitly Deferred

- Conversion Events (7B.5)
- Revenue / invoice / payment attribution
- Historical backfill of legacy `leads.source`
- Syncing coarse `leads.source` from attribution (contract compatibility field — deferred so this phase never writes marketing metadata onto CRM rows)
- Provider integrations, reporting, dashboards

## Did Any Architectural Assumptions Change?

- No. The approved principle holds: attribution is a relationship, not a mutation. Marketing data stays on the Marketing Platform; CRM holds FKs only.
- One practical detail vs the TDS ER diagram naming: the table is `marketing_attributions` / model `MarketingAttribution` (prompt-specified) rather than `lead_attribution`. Semantics match the contract's Lead Attribution record for the first_touch / lifecycle relationship scope of this phase.

## CTO Recommendation

Proceed to Phase 7B.5 (Conversion Events) after review. That phase should emit append-only conversion events (`lead_created`, `lead_converted`, `opportunity_created`, …) from the existing CRM service hooks without changing this attribution relationship layer.
