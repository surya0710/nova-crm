# P7 Marketing Attribution Platform - Phase 7B.5 Impact Report

## Phase

Phase 7B.5 - Conversion Events

## What Changed?

- Added additive, reversible migration `marketing_conversions` for immutable, organization-scoped conversion events linked to `marketing_attributions`.
- Added `MarketingConversion` model with `BelongsToOrganization`, supported event constants, and boot-time guards that reject updates and deletes.
- Added `MarketingConversionService` as the single write authority: resolve attribution, validate canonical event names, prevent duplicates, record events, expose history.
- Added thin `OpportunityService` for stage transitions so `opportunity_won` is recorded in the service layer (not via observers or controller writes). `OpportunityController::updateStage` now delegates to it.
- Hooked conversion recording into existing CRM services only:
  - `LeadService::create` / `createFromApi` → `lead_created` (after attribution)
  - `LeadConversionService::convert` → `lead_converted`, `customer_created` (new customers only), `opportunity_created`
  - `OpportunityService::updateStage` → `opportunity_won` when stage becomes `closed_won`
- Added `config/marketing.php` `conversions.supported_events` list.
- Added `tests/Feature/MarketingConversionTest.php` (13 tests, 45 assertions).

## Architecture

```
LeadService / LeadConversionService / OpportunityService
        ↓
MarketingConversionService          ← single write authority
        ↓
MarketingAttributionService         ← attribution resolution (required)
        ↓
MarketingConversion (immutable row)
```

Platform stack after this phase:

```
MarketingTrackingService
        ↓
MarketingChannelClassificationService
        ↓
MarketingAttributionService
        ↓
MarketingConversionService
```

Each service retains one responsibility. Controllers remain thin. No model observers. No Event Sourcing — conversions are plain append-only Eloquent rows.

## Conversion Lifecycle

```
Attributed Lead create
        → lead_created

Lead convert (with attribution)
        → lead_converted
        → customer_created   (only when a new customer is created)
        → opportunity_created (only when an opportunity is created)

Opportunity marked closed_won (with attribution)
        → opportunity_won (event_value = opportunity amount, currency preserved)
```

If no `MarketingAttribution` exists for the CRM entity, no conversion is recorded. CRM create/convert/stage flows continue unchanged.

## Supported Events

Canonical names only (no aliases, no provider-specific naming):

| Event | Subject | Source |
| --- | --- | --- |
| `lead_created` | Lead | `LeadService` |
| `lead_converted` | Lead (+ customer/opportunity FKs) | `LeadConversionService` |
| `customer_created` | Customer | `LeadConversionService` |
| `opportunity_created` | Opportunity | `LeadConversionService` |
| `opportunity_won` | Opportunity (value + currency) | `OpportunityService` |

Deferred by design (future phases / contract extension): `lead_qualified`, `invoice_paid`, `offline_conversion`.

## Event Immutability

- Model `updating` and `deleting` hooks throw `RuntimeException`.
- Service exposes create/read only — no update or delete API.
- Business state changes later must emit a new event; history is never rewritten.

## CRM Integration Points

| CRM operation | Marketing hook |
| --- | --- |
| Lead create (web + API) | `recordLeadCreated` after `attributeLead` |
| Lead convert | `recordLeadConverted` / `recordCustomerCreated` / `recordOpportunityCreated` after `propagateToConversion` |
| Opportunity stage → `closed_won` | `recordOpportunityWon` inside `OpportunityService::updateStage` |

No controllers insert conversion rows. No Eloquent model events/observers.

## Attribution Resolution

Every conversion resolves attribution through `MarketingAttributionService` (`findPrimaryForLead` / `findForCustomer` / `findForOpportunity`). No attribution → null return → no write. Marketing tracking fields (UTMs, click IDs, channel) are stripped from metadata if supplied and never stored on conversion rows.

## Duplicate Prevention

- Service-level: one canonical event per subject (`lead_id` for lead events, `customer_id` for `customer_created`, `opportunity_id` for opportunity events). Re-calls return the existing row.
- Database unique indexes: `mkt_conv_event_lead_unique`, `mkt_conv_event_customer_unique`, `mkt_conv_event_opp_unique`.

## Tenant Isolation

- Every conversion requires `organization_id` matching the attribution and all referenced CRM entities.
- `BelongsToOrganization` + `OrganizationScope` apply.
- Cross-tenant entity mixes are refused (no write).

## Future Provider Integrations

These events are the canonical feed for later Meta Offline Conversions, Google Offline Conversions, reporting, ROAS/CPL, AI, and automation. Providers will consume `MarketingConversion` rows via export adapters — they never define event names or write this table directly.

## Testing Summary

- `php artisan test --filter=Marketing` — 82 passed (314 assertions): prior 7B.1–7B.4 suites green plus 13 new conversion tests covering lead_created, duplicate prevention, no-attribution skip, conversion cascade events, opportunity_won with value, immutability, tenant scoping, HTTP pipeline won path, and tracking/attribution regression.
- `php artisan test` (full suite) — 529 passed (1723 assertions), 0 failures. Baseline 516 fully green plus 13 new tests. CRM, Revenue, Metadata, and prior Marketing phases unaffected.

## Performance Considerations

- Conversion write is a single insert after one attribution lookup and one duplicate existence check — no touch-table scans.
- Indexes support reporting reads by `(organization_id, event_name, occurred_at)` and attribution FK without scanning the full table.
- No queues, caches, or cleanup jobs in this phase.

## Did Any Architectural Assumptions Change?

- No. Conversions remain immutable business events; Marketing stays the source of truth for attribution; CRM services emit conversions additively.
- One additive platform service (`OpportunityService`) was introduced because stage updates previously lived only in the controller; moving them into a service is required to keep conversion hooks out of controllers and observers, consistent with NovaCRM architecture.

## Explicitly Deferred

- Revenue attribution, ROAS, CPL, CAC
- Reporting / dashboards
- Provider offline conversion uploads
- Historical replay / backfill (7B.6)
- Queues / webhooks
- `lead_qualified` / `invoice_paid` / `offline_conversion` event types

## CTO Recommendation

Proceed to Phase 7B.6 (Historical Attribution Backfill) or the next planned sub-phase after review. Downstream reporting and provider upload phases should consume `marketing_conversions` as the sole conversion truth and must not re-infer lifecycle events from CRM tables.
