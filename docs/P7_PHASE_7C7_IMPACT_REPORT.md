# P7 Marketing Attribution Platform - Phase 7C.7 Impact Report

## Phase

Phase 7C.7 - Meta Webhook Event Processing

## What Changed?

Implemented **processing of stored Meta webhook events into CRM leads**, reusing the exact same import pipeline as manual import. A webhook is treated as a notification: it tells NovaCRM a lead exists; the complete lead is then fetched through the Graph API before a CRM Lead is created.

There is **one** lead-creation path. Manual import and webhook processing both call `MarketingProviderService::importNormalizedEntry()`.

### Webhook processor

- New service: `App\Services\Marketing\Providers\MetaWebhookProcessor`
- `processPending(string $slug = 'meta', ?int $limit = null)` — selects stored `received` events, isolates per-event failures, aggregates stats
- `process(MarketingProviderWebhookEvent $event)` — delegates to the service; wraps unexpected errors so a single event never aborts a batch
- No CRM persistence and no lead creation in the processor — all writes go through `MarketingProviderService`

### Single lead-retrieval capability

- `MetaGraphClient::getLead($leadId, $accessToken)` — `GET /{lead-id}` with the same field set as `listFormLeads`
- Additive optional contract: `App\Contracts\MarketingProviderLeadRetrievalInterface::retrieveLeadEntry()`
- `MetaMarketingProvider::retrieveLeadEntry()` fetches and normalizes a single lead via `normalizeLeadEntryDto` — identical DTO shape to manual import; no Eloquent writes
- Frozen `MarketingProviderInterface` and the 7C.5 `MarketingProviderLeadImportInterface` were **not** modified

### Shared import pipeline (single write authority)

`MarketingProviderService::importNormalizedEntry()` extracted from `importLeadEntries()`:

```
Normalized Lead DTO
  → dedup check (marketing_provider_imported_leads)
  → LeadService::create()
  → imported-lead registry row
  → outcome {imported | skipped | failed}
```

- `importLeadEntries()` (manual) now loops through this shared method — behavior unchanged
- `processWebhookEvent()` (webhook) resolves org + retrieves lead, then calls the same method
- Dedup existence check runs `withoutGlobalScope(OrganizationScope)` and filters explicitly by `organization_id`, so it is correct both inside a tenant request and in an unauthenticated webhook context

### Organization resolution

`MarketingProviderService::resolveWebhookProvider($slug, $pageId, $formId)`:

- Reads only stored `marketing_provider_credentials.configuration` (`page_id`, `lead_form_ids`)
- Request parameters are never trusted; tenants are never inferred
- Form-id match is authoritative; page-id match is fallback
- Returns exactly one provider, or a reason: `no_organization` / `ambiguous_organization`
- Queries run `withoutGlobalScope(OrganizationScope)` so resolution works without an ambient tenant and cannot be skewed by one

### Webhook event lifecycle

Additive migration `add_processing_fields_to_marketing_provider_webhook_events`:

| Column | Purpose |
| --- | --- |
| `failure_reason` | Text; recorded failure detail (never raw payloads) |
| `processing_attempts` | Increments each processing attempt |

Model states (`MarketingProviderWebhookEvent`): `received` → `processing` → `processed` \| `failed` \| `ignored` (plus foundation states `verified`, `duplicate`, `rejected`). Events are **never deleted**. `processed_at` and `failure_reason` are persisted on finalization.

### Lead attribution actor

Webhook-created leads are attributed to the resolved organization's `primaryOwner()` for `created_by`. No schema change to `leads`. If an organization has no user, the event fails safely.

### Integration UI

Meta integration details now show:

- Last Webhook Received
- Last Webhook Processed
- Last Processing Result
- Processed Count / Failed Count
- **Process Webhook Events** button (manual trigger; count of pending) → `POST integrations/{provider}/webhooks/process`

Raw payloads are never exposed.

### What did not change

- Marketing Platform contracts / runtime / tables
- Manual import behavior (same pipeline, same results)
- CRM, Revenue, Metadata Platform
- No second lead-creation path

## Architecture

```
Meta
  → Webhook (7C.6: verified + signature-checked + stored)
      → marketing_provider_webhook_events (received)
  → MetaWebhookProcessor::processPending
      → MarketingProviderService::processWebhookEvent   ← single write authority
          → resolveWebhookProvider (page_id / form_id → organization)
          → MetaMarketingProvider::retrieveLeadEntry
              → MetaGraphClient::getLead
          → importNormalizedEntry (SHARED with manual import)
              → dedup → LeadService::create → imported-lead registry
          → event lifecycle: processed | failed | ignored
```

## Webhook Lifecycle

```
received
  → processing (attempts++)
     → leadgen changes?  no  → ignored (processed_at set)
                          yes → resolve org
                                 unresolved → failed (reason)
                                 resolved   → retrieve lead
                                               retrieval fail → failed (reason, provider status)
                                               retrieved      → shared import
                                                                 imported / skipped
  → processed (all non-failing)  |  failed (only failures)
```

## Duplicate Prevention

Two layers, both idempotent:

1. **Delivery dedup (7C.6):** identical raw bodies map to one stored event (`provider + delivery_id` unique).
2. **Lead dedup (7C.5, shared):** `(organization_id, marketing_provider_id, external_lead_id)` unique in `marketing_provider_imported_leads`. Distinct deliveries referencing the same `leadgen_id` create exactly one CRM lead; repeats are skipped.

## Failure Handling

| Condition | Behavior |
| --- | --- |
| Unknown / ambiguous organization | Event `failed`, reason recorded, no lead |
| Revoked permissions | Event `failed`, provider → `error` |
| Expired credentials | Event `failed`, provider → `expired` |
| Deleted form / lead (missing) | Event `failed`, reason recorded |
| Graph API error | Event `failed`, no crash |
| Non-leadgen / empty change | Event `ignored` |
| Duplicate delivery / duplicate lead | Skipped safely, idempotent |
| Unexpected exception | Isolated per event; batch continues |

No queues, jobs, retry scheduler, or automatic replay were introduced.

## Multi-Tenancy

Every event resolves to exactly one organization via stored configuration. Leads always carry the resolved `organization_id`. Cross-tenant processing cannot occur; resolution failures fail safely without creating data.

## Testing Summary

New suite: `tests/Feature/MetaWebhookProcessingTest.php` (17 tests)

Processing:
- Successful processing creates a lead (no visitor → no attribution/conversion writes)
- Lead details fetched from Graph, not the webhook payload
- Organization resolution is tenant-isolated by form
- Duplicate delivery stored/processed once
- Duplicate lead prevented across distinct events
- Manual import and webhook share the dedup pipeline

Failures:
- Unknown organization → failed
- Revoked permissions → failed (+ provider error)
- Expired credentials → failed (+ provider expired)
- Deleted lead/form → failed
- Graph API failure → failed, no crash
- Non-leadgen event → ignored
- Partial failures don't block other events
- Idempotent reprocessing of finalized events

UI / helpers:
- Webhook status reports processing stats
- UI process button triggers processing
- Resolution helper reports reasons

Regression suites (all green):

| Suite | Result |
| --- | --- |
| Marketing + Meta + Integration | **339 passed (1400 assertions)** |
| Full suite | **645 passed (2278 assertions)**, 0 failures |

Quality gate delta vs Phase 7C.6: +17 tests, +84 assertions.

## CTO Recommendation

Meta Webhook Event Processing is complete: stored events are processed, ownership is resolved safely, complete lead details are fetched via Graph, and manual/webhook flows share one pipeline with enforced idempotency. Proceed to Phase 7C.8 (Meta Offline Conversion Uploads) only after review. Do not add queues, retries, or automatic replay in a later phase without an explicit runtime contract.
