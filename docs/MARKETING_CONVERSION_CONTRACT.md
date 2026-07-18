# Marketing Conversion Contract

## Status

- Phase: P7B.F (Foundation Freeze)
- State: **Frozen**
- Companion documents: `docs/MARKETING_ATTRIBUTION_RUNTIME_CONTRACT.md`, `docs/MARKETING_ATTRIBUTION_CONTRACT.md`
- Implementation reference: Phase 7B.5 (Conversion Events)

## Purpose

This contract freezes the canonical vocabulary of business-value events that the Marketing Platform records against attributed identities. Conversion events are the sole feed for future provider offline uploads, revenue attribution reports, workflow automation, and AI consumers.

`MarketingConversionService` is the **single write authority** for `marketing_conversions`.

## Single Write Authority

| Concern | Authority |
| --- | --- |
| Record conversion events | `MarketingConversionService` |
| Resolve attribution for an event | Via `MarketingAttributionService` finders |
| Read conversion history | `MarketingConversionService` history methods |

Rules:

- Providers never write conversion events directly.
- Controllers never insert conversion rows.
- No Eloquent model observers for conversion emission.
- CRM services emit conversions additively through the conversion service API.

## Canonical Event Vocabulary

Closed set for the frozen platform (`config('marketing.conversions.supported_events')` and `MarketingConversion::SUPPORTED_EVENTS`):

| Event name | Subject | Emitted when | Value |
| --- | --- | --- | --- |
| `lead_created` | Lead | Attributed lead is created | null |
| `lead_converted` | Lead (+ customer/opportunity FKs) | Lead converts to customer | null |
| `customer_created` | Customer | Conversion creates a **new** customer | null |
| `opportunity_created` | Opportunity | Conversion creates an opportunity | null |
| `opportunity_won` | Opportunity | Opportunity stage becomes `closed_won` | Opportunity amount + currency |

Rules:

- Event names are canonical. No aliases. No provider-specific naming (`Purchase`, `Lead`, etc.).
- Providers map **from** these names outbound; they never define inbound vocabulary.
- Extending the vocabulary requires contract revision and a config entry.

### Deferred events (not yet implemented)

| Event | Intended source |
| --- | --- |
| `lead_qualified` | Lead status → qualified |
| `invoice_paid` | Payment recorded |
| `offline_conversion` | Import / offline upload |

## Conversion Lifecycle

```
Attributed Lead create
        → lead_created

Lead convert (with attribution)
        → lead_converted
        → customer_created      (only when a new customer is created)
        → opportunity_created   (only when an opportunity is created)

Opportunity → closed_won (with attribution)
        → opportunity_won (event_value = opportunity amount)
```

If no `MarketingAttribution` exists for the CRM entity, **no conversion is recorded**. CRM create/convert/stage flows continue unchanged.

## Immutability

- Conversion events are append-only.
- Model `updating` and `deleting` hooks throw `RuntimeException`.
- Service exposes create/read only — no update or delete API.
- Business state changes later must emit a **new** event; history is never rewritten.

Marketing tracking fields (UTMs, click IDs, channel) must not be stored on conversion rows. That data lives on touches and is joined through attribution.

## Duplicate Prevention

| Event class | Uniqueness subject |
| --- | --- |
| Lead events (`lead_created`, `lead_converted`) | `(event_name, lead_id)` |
| `customer_created` | `(event_name, customer_id)` |
| Opportunity events (`opportunity_created`, `opportunity_won`) | `(event_name, opportunity_id)` |

Rules:

- Service-level: re-calls return the existing row.
- Database unique indexes enforce the same guarantees.
- Conversion replay during backfill is therefore safe and idempotent.

## Tenant Isolation

- Every conversion requires `organization_id` matching the attribution and all referenced CRM entities.
- `BelongsToOrganization` + `OrganizationScope` apply.
- Cross-tenant entity mixes are refused (no write).

## CRM Integration Points (Frozen Hooks)

| CRM operation | Marketing hook |
| --- | --- |
| `LeadService::create` / `createFromApi` | `recordLeadCreated` after `attributeLead` |
| `LeadConversionService::convert` | `recordLeadConverted` / `recordCustomerCreated` / `recordOpportunityCreated` after `propagateToConversion` |
| `OpportunityService::updateStage` → `closed_won` | `recordOpportunityWon` |

## Event Record Shape

Minimum fields:

- `organization_id`
- `marketing_attribution_id`
- `event_name`
- `occurred_at`
- Nullable: `lead_id`, `customer_id`, `opportunity_id`, `event_value`, `currency`, `metadata`

Monetary value semantics:

- `opportunity_won` carries pipeline value (opportunity amount).
- Future `invoice_paid` will carry collected revenue (payment amount).
- Reports must label which value definition they use. Collected revenue remains the default revenue definition for financial reports, consistent with existing `RevenueService`.

## Future Provider Mappings

Providers consume conversions via export adapters. Example mappings (illustrative; exact payload construction is Phase 7C+):

| Platform event | Meta (example) | Google Ads (example) |
| --- | --- | --- |
| `lead_created` | Lead / CompleteRegistration | Lead form / conversion action |
| `lead_converted` | Lead | Conversion action |
| `opportunity_won` | Purchase (value) | Conversion with value |
| `invoice_paid` (future) | Purchase (value) | Enhanced conversion with value |

Rules for future adapters:

- Read only from `marketing_conversions` (+ attribution / touch for click IDs).
- Never invent event names.
- Never write this table.
- Capability-gated (`offline_push`); absence of capability means skip, not fail platform writes.

## Extension Rules

Allowed with contract revision:

- New canonical event names in config + model constants.
- Additional CRM emission hooks (e.g. payment recording → `invoice_paid`).

Prohibited:

- Provider-defined event vocabularies as platform truth.
- Updating or deleting conversion rows.
- Recording conversions without attribution.
- Storing UTM/click-ID snapshots on conversion rows.
- Controller or observer-based emission that bypasses `MarketingConversionService`.

## Non-Responsibilities

- Spend / ROAS / CPL calculation (reporting consumers).
- Offline conversion HTTP upload (provider adapters).
- Attribution relationship creation (Attribution Runtime Contract).
- Heuristic reconstruction of lifecycle events from CRM tables by reports — reports must read this table.
