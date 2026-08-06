# P7 Marketing Attribution Platform - Phase 7C.9 Impact Report

## Phase

Phase 7C.9 - Meta Offline Conversion Uploads

## What Changed?

Implemented the first **outbound** synchronization capability on the Phase 7C.8 Synchronization Runtime: uploading existing Marketing Platform conversion events to Meta via the Conversions API (Pixel events).

No scheduling, queues, retries, campaign sync, audience sync, reporting, or AI were added. Marketing Platform contracts and write authorities remain unchanged.

### Conversion source (read-only)

Uploads consume canonical `marketing_conversions` rows only:

- `lead_created`
- `lead_converted`
- `customer_created`
- `opportunity_created`
- `opportunity_won`

`MarketingConversionService` remains the sole writer of conversion events. Provider uploads never create, update, or delete conversion rows.

### Meta adapter

`MetaMarketingProvider`:

- Declares capability `offline_conversions`
- Implements `uploadConversions()`:
  - Requires connected credentials and configured `pixel_id`
  - Maps canonical events → Meta standard events
  - Hashes email/phone (SHA-256), builds `fbc` from `fbclid` when present
  - Calls `MetaGraphClient::sendPixelEvents()` one event at a time
  - Continues on per-event failures; expired/revoked tokens fail remaining events
  - Returns normalized `{uploaded, failed, results}` — **no Eloquent writes**

Canonical → Meta mapping:

| Canonical | Meta `event_name` |
| --- | --- |
| `lead_created` | `Lead` |
| `lead_converted` | `Lead` |
| `customer_created` | `CompleteRegistration` |
| `opportunity_created` | `InitiateCheckout` |
| `opportunity_won` | `Purchase` |

### MetaGraphClient

Added `sendPixelEvents($pixelId, $accessToken, $events, $testEventCode = null)` posting to `POST /{pixel-id}/events`, plus a shared `post()` helper. Authentication uses the tenant access token; platform app secret is not sent with CAPI payloads.

### MarketingProviderService (orchestration authority)

`uploadConversions()` now:

1. Loads pending org conversions (or accepts explicit DTOs)
2. Skips already-uploaded conversions (dedup registry)
3. Starts a Synchronization Runtime run (`conversion_upload` / `outbound`)
4. Delegates to the adapter
5. Persists successful uploads in the dedup registry
6. Records processed / uploaded / skipped / failed on the sync run
7. Finishes the run (`completed` | `partial` | `failed`)

Helpers:

- `supportsOfflineConversions()`
- `latestConversionUploadRun()`

### Duplicate prevention

Additive table `marketing_provider_uploaded_conversions`:

| Column | Purpose |
| --- | --- |
| `organization_id` | Tenant ownership |
| `marketing_provider_id` | Provider connection |
| `marketing_conversion_id` | Marketing Platform conversion FK |
| `external_event_id` | Sent `event_id` (`nova_crm_conversion_{id}`) |
| `provider_event_name` | Meta event name |
| `metadata` | Provider response diagnostics |
| `uploaded_at` | Upload timestamp |

Unique `(organization_id, marketing_provider_id, marketing_conversion_id)` ensures each conversion uploads at most once per provider unless a future replay mechanism explicitly requests otherwise.

### Integration UI

On connected Meta integration details (when `offline_conversions` is supported):

- **Upload Conversions** button (manual only; disabled without `pixel_id`)
- Last upload time, uploaded count, failed count, synchronization status
- Route: `POST integrations/{provider}/conversions/upload`

No scheduling or retry UI.

### What did not change

- Frozen Marketing Platform contracts / services / tables (except additive upload registry)
- Frozen Provider Platform interface contract (`MarketingProviderInterface` signature unchanged)
- Frozen Synchronization Runtime contracts (reused as-is)
- CRM / Revenue / Metadata Platform
- No queues, workers, retries, or automatic uploads

## Architecture

```text
MarketingConversion (SoT)
        |
        v
MarketingProviderService::uploadConversions
        |
        +--> marketing_provider_sync_runs (runtime history)
        +--> marketing_provider_uploaded_conversions (dedup)
        |
        v
MetaMarketingProvider::uploadConversions
        |
        v
MetaGraphClient::sendPixelEvents
        |
        v
Meta Conversions API  POST /{pixel-id}/events
```

## Conversion Upload Lifecycle

```text
Admin clicks Upload Conversions
  → resolve tenant Meta connection + pixel_id
  → load marketing_conversions for organization
  → skip rows already in uploaded_conversions
  → startSynchronization(conversion_upload, outbound)
  → for each pending DTO:
       map event + hash PII + optional fbc
       POST pixel events
       on success → record uploaded_conversions row
  → finishSynchronization(completed|partial|failed)
```

## Synchronization Runtime Integration

| Field | Value |
| --- | --- |
| `sync_type` | `conversion_upload` |
| `direction` | `outbound` |
| `records_processed` | uploaded + skipped + failed |
| `records_succeeded` | uploaded |
| `records_failed` | failed |
| `metadata.skipped` | already-uploaded count |
| `metadata.uploaded` / `failed` | explicit counters |

## Security Considerations

- Uses only the initiating organization's encrypted access token and configured `pixel_id`
- Cross-tenant provider IDs cannot be resolved or uploaded against
- PII is hashed before leaving Konnect Nex; tokens never appear in UI
- Invalid / expired credentials mark provider status without deleting history or conversion rows
- Failed auth/API responses do not create upload registry rows

## Multi-Tenancy

Each organization uploads only its own conversions through its own Meta connection and pixel. Dedup keys and sync runs always carry `organization_id`.

## Testing Summary

New suite: `tests/Feature/MetaOfflineConversionUploadTest.php` (8 tests)

Coverage:

- Capability declaration
- Successful CAPI upload + sync history + dedup row
- Duplicate skip on re-upload
- Expired credentials → failed run, no upload rows, provider expired
- Partial failures → `partial` status, continue processing
- Tenant isolation
- Integration UI upload + stats
- Marketing conversion immutability / no conversion table writes

Regression suites (all green):

| Suite | Result |
| --- | --- |
| Provider + Meta + Integration | **119 passed (592 assertions)** |
| Marketing filter | **141 passed (586 assertions)** |
| Full suite | **661 passed (2373 assertions)**, 0 failures |

Quality gate delta vs Phase 7C.8: **+8 tests, +45 assertions**.

## CTO Recommendation

Meta Offline Conversion Uploads are complete as a manual, runtime-orchestrated outbound sync. Proceed to scheduling / queue workers only with an explicit runtime contract. Do not begin campaign or audience synchronization in this phase.
