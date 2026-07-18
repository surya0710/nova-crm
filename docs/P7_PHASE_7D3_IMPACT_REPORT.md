# P7 Marketing Attribution Platform - Phase 7D.3 Impact Report

## Phase

Phase 7D.3 - Google Ads Offline Conversion Uploads

## Outcome

Google Ads now supports administrator-triggered outbound conversion uploads
through the existing Provider Synchronization Runtime. The adapter consumes the
five canonical Marketing Platform conversion events, maps each event to an
administrator-selected Google Ads conversion action, and submits enhanced
click conversions through the Google Ads API.

The implementation is manual-only. No scheduling, queues, workers, retry
engine, campaign synchronization, audience synchronization, reporting, or AI
optimization was added.

No frozen Marketing Platform contract, Provider Platform contract,
Synchronization Runtime model/migration, controller, or route was modified.
`MarketingProviderService` received additive DTO/sync-metadata fields only.

## Upload Architecture

```text
MarketingConversion (immutable source of truth)
        |
        v
MarketingProviderService::uploadConversions       existing, unchanged authority
        |
        +--> prepare tenant conversion DTOs
        +--> skip provider-specific duplicates
        +--> marketing_provider_sync_runs
        |
        v
GoogleAdsProvider::uploadConversions               provider mapping only
        |
        +--> selected customer + conversion actions
        +--> canonical event -> action category
        +--> hashed enhanced-conversion identifiers
        |
        v
GoogleAdsClient::uploadOfflineConversions          HTTP/response normalization
        |
        v
Google Ads API
POST customers/{customerId}:uploadClickConversions
```

The generic route and controller action are reused:

- `POST integrations/{provider}/conversions/upload`
- `IntegrationController::uploadConversions`

Adding `offline_conversions` to the Google adapter capabilities automatically
activates the existing orchestration, history lookup, and Integration
Management section.

## Google Ads Provider Behavior

`GoogleAdsProvider::uploadConversions()` performs no Eloquent writes. It:

1. Requires a tenant access token, non-expired credentials,
   `configuration.customer_id`, and selected
   `configuration.conversion_action_ids`.
2. Reads the selected customer's time zone and current conversion actions.
3. Keeps only selected actions that still exist, are `ENABLED`, and have type
   `UPLOAD_CLICKS`.
4. Maps each canonical conversion event to a selected action.
5. Normalizes provider payloads and delegates one partial-failure-enabled batch
   to `GoogleAdsClient`.
6. Returns normalized per-conversion results to
   `MarketingProviderService`.

Removed, inactive, and non-upload actions never receive data. An invalid
conversion does not prevent other valid conversions in the batch from being
processed.

## Google Conversion Mapping

The Marketing Platform vocabulary remains unchanged:

| Canonical event | Preferred Google Ads action categories |
| --- | --- |
| `lead_created` | `SUBMIT_LEAD_FORM`, `LEAD`, `IMPORTED_LEAD`, `CONTACT` |
| `lead_converted` | `CONVERTED_LEAD`, `QUALIFIED_LEAD`, `LEAD` |
| `customer_created` | `SIGNUP` |
| `opportunity_created` | `BEGIN_CHECKOUT`, `REQUEST_QUOTE` |
| `opportunity_won` | `PURCHASE`, `STORE_SALE` |

When one valid conversion action is selected, it is deliberately used for all
five supported events. This supports organizations that maintain one aggregate
offline action. When multiple actions are selected, category matching provides
deterministic event-to-action routing. If no selected action matches an event,
that event fails explicitly instead of being sent to an arbitrary action.

Every outbound conversion contains:

- `conversionAction`:
  `customers/{customerId}/conversionActions/{conversionActionId}`
- `conversionDateTime`, rendered in the selected customer's Google Ads time
  zone
- stable `orderId`: `nova_crm_conversion_{marketing_conversion_id}`
- normalized SHA-256 `hashedEmail` and/or `hashedPhoneNumber` when present
- optional Google click identifier from attribution touches (`gclid`) or when an
  explicit DTO supplies `gclid`, `gbraid`, or `wbraid`
- optional `conversionValue` and uppercase `currencyCode`

Email is trimmed and lowercased before hashing. Phone numbers are reduced to
digits before hashing. Raw email and phone values are never sent.

## GoogleAdsClient

`GoogleAdsClient::uploadOfflineConversions()` contains communication and
response normalization only:

- sends authenticated JSON with tenant bearer token and platform developer
  token
- uses `partialFailure=true`
- submits all prepared conversions in one request
- maps Google `partialFailureError.details[].errors[]` back to conversion
  indexes
- returns normalized success/failure rows and `jobId`
- treats malformed/missing per-conversion results as failures
- continues using the existing bounded Google error normalization

It contains no conversion-event mapping and performs no persistence.

## Synchronization Lifecycle

`MarketingProviderService::uploadConversions()` is reused unchanged:

```text
load organization conversions
  -> remove rows already uploaded by this provider
  -> startSynchronization(conversion_upload, outbound)
  -> call GoogleAdsProvider
  -> persist successful dedup rows
  -> update processed / succeeded / failed totals
  -> finish completed | partial | failed
```

Runtime fields:

| Field | Meaning |
| --- | --- |
| `sync_type` | `conversion_upload` |
| `direction` | `outbound` |
| `records_processed` | uploaded + skipped + failed |
| `records_succeeded` | uploaded |
| `records_failed` | failed |
| `metadata.uploaded` | successful Google uploads |
| `metadata.skipped` | provider-specific duplicate count |
| `metadata.failed` | failed conversions |
| `metadata.errors` | bounded per-conversion error samples |

`MarketingProviderService` remains the only synchronization and persistence
authority. The adapter never creates sync runs or uploaded-conversion rows.

## Duplicate Prevention

The existing `marketing_provider_uploaded_conversions` registry is reused
without modification.

The unique key is:

```text
(organization_id, marketing_provider_id, marketing_conversion_id)
```

Consequences:

- one Marketing Conversion uploads at most once through one Google connection
- repeated manual uploads are skipped and still produce synchronization
  history
- Meta and Google dedup independently because their provider connection IDs
  differ
- failed Google conversions are not registered and remain eligible for a
  future manual attempt

No replay or retry engine was introduced.

## Error Handling

| Condition | Behavior |
| --- | --- |
| Missing customer selection | Failed run; no API upload |
| No selected conversion action | Failed run; no API upload |
| Removed action | Affected conversion fails; no dedup row |
| Inactive action | Affected conversion fails; no dedup row |
| Non-`UPLOAD_CLICKS` action | Affected conversion fails; no dedup row |
| Unsupported canonical event | Conversion fails locally; others continue |
| Missing usable identifier | Conversion fails locally; others continue |
| Google partial failure | Successful rows registered; run becomes `partial` |
| Local credential expiry | Provider becomes `expired`; failed run; no HTTP |
| Revoked/unauthenticated token | Provider becomes `expired`; failed run |
| Other API failure | Provider becomes `error`; failed run |
| Malformed success response | Missing result is normalized as a failure |

Synchronization history and immutable Marketing Conversion rows are retained
for every outcome.

## Security and Multi-Tenancy

- Every upload starts from the current organization's resolved provider
  connection.
- Only that credential's encrypted bearer token, configured customer ID, and
  selected conversion action IDs are used.
- Conversion loading, dedup lookup, sync runs, and upload registry rows are
  explicitly organization/provider scoped.
- Cross-tenant provider IDs and credentials are never resolved.
- Tenant access/refresh tokens remain encrypted and are never rendered.
- Tenant PII is normalized and SHA-256 hashed before leaving NovaCRM.
- Submitted conversion actions are re-read from Google before every upload;
  stale configuration cannot silently target a removed action.

## Integration Management

The existing provider-agnostic Offline Conversions section now appears for
Google:

- Upload Conversions (manual trigger)
- Last upload timestamp
- Uploaded count
- Failed count
- synchronization status

The button is enabled for:

- Meta when a Pixel is selected
- Google when both a Customer Account and at least one Conversion Action are
  selected

No provider-specific page, controller action, route, schedule, or retry control
was added.

## Meta Offline Conversion Reuse

Reused without modification:

- frozen `MarketingProviderInterface::uploadConversions()` signature
- `MarketingProviderService::uploadConversions()`
- conversion DTO preparation from immutable Marketing Platform rows (including
  `gclid` resolution from attribution touches, mirroring Meta `fbclid`)
- `MarketingProviderSyncRun` lifecycle and canonical vocabulary
- `startSynchronization`, `updateSynchronizationProgress`,
  `finishSynchronization`, and failure handling
- `MarketingProviderUploadedConversion` model, table, uniqueness, and factory
- pending conversion loading and duplicate filtering
- uploaded-conversion persistence
- provider status application
- `supportsOfflineConversions()` capability detection
- `latestConversionUploadRun()`
- `IntegrationController::uploadConversions()`
- existing upload route, permissions, flash messages, and stats UI

Google-specific additions:

- Google conversion-action/category mapping in `GoogleAdsProvider`
- Google enhanced click-conversion payload normalization
- Google partial-failure response normalization in `GoogleAdsClient`
- a capability-aware Google button enablement condition and provider-specific
  empty-state copy in the shared view
- Google-specific tests and this report

Structurally, the entire Meta orchestration, synchronization history, duplicate
registry, HTTP entry point, and result persistence path was reused. There is no
second synchronization runtime and no provider-specific upload table.

### Post-trace follow-up (7D.3 polish)

After [Trace conversion upload flow](9d492b3c-c665-4502-b10b-89d7b936ebb3),
three small platform/UI gaps were closed without changing frozen contracts:

1. `MarketingProviderService::mapConversionToUploadDto()` now resolves `gclid`
   from attribution touches (same pattern as Meta `fbclid`).
2. Conversion-upload sync-run metadata now persists `customer_id` from Google
   adapter results (alongside existing Meta `pixel_id`).
3. Offline Conversions empty-state copy is provider-aware (Pixel vs Customer +
   Conversion Actions).

## Testing Summary

New suite:

- `tests/Feature/GoogleAdsOfflineConversionUploadTest.php` (12 tests)

Coverage:

- capability declaration
- all five canonical events mapped to selected conversion actions
- enhanced conversion hashing, customer time zone, value, currency, and `gclid`
- gclid-only upload eligibility when email/phone are absent
- successful batch upload, dedup rows, and completed synchronization history
- duplicate prevention and Meta/Google independence
- invalid conversion action type
- removed conversion action
- locally expired credentials
- revoked credentials
- Google partial failures and partial runtime status
- tenant isolation
- Integration Management upload action and statistics
- Marketing Conversion immutability

Updated:

- `GoogleAdsProviderTest` capability expectations

Verification:

- Google Ads suites: **39 passed (248 assertions)**
- Provider runtime / Integration / Meta offline / Google regression:
  **63 passed (390 assertions)**
- Marketing suite: **143 passed (591 assertions)**
- Full suite: **700 passed (2621 assertions)**, 0 failures
- Quality gate delta from Phase 7D.2: **+11 tests, +68 assertions**
- Formatting: Pint passed

## What Did Not Change

- frozen Marketing Platform contracts and services
- canonical conversion vocabulary
- immutable `marketing_conversions`
- frozen Provider Platform contracts
- Provider Registry architecture
- Synchronization Runtime code and schema
- uploaded-conversion registry code and schema
- Integration controller and routes
- Meta provider behavior
- CRM, Revenue, and Metadata Platform

## Completion

- Google Offline Conversion Uploads work. Done.
- Synchronization Runtime reused unchanged. Done.
- Marketing Platform remains unchanged. Done.
- Duplicate uploads prevented independently per provider. Done.
- Synchronization history records processed/uploaded/skipped/failed. Done.
- MarketingProviderService remains orchestration authority. Done.
- Administrator-selected Customer Account and Conversion Actions are enforced.
  Done.
- Comprehensive tests added. Done.
- Full suite passes with zero regressions. Done.

Phase 7E.1 Provider Diagnostics has not been started.
