# P7 Marketing Attribution Platform - Phase 7D.2 Impact Report

## Phase

Phase 7D.2 - Google Ads Customer & Conversion Asset Discovery

## Outcome

The Google Ads adapter now discovers accessible customer accounts and their
conversion actions, and administrators can select the primary customer account
and conversion actions from the existing Integration Management page.
Selections persist inside `MarketingProviderCredential.configuration` through
the existing Provider Platform write path.

The phase is discovery-only. Offline conversion uploads, campaign discovery,
campaign/audience synchronization, reporting, scheduling, queue workers, and
retry engines remain unimplemented.

No frozen contract, platform service, controller, route, model, or migration
required modification. The synchronization runtime was not touched.

## Discovery Architecture

```text
Integration Management (integrations/show)      existing page, extended view only
        |
        v
IntegrationController                            existing, unchanged
  show / saveAssets / refreshAssets
        |
        v
MarketingProviderService                         existing, unchanged
  discoverAssets / saveAssetConfiguration /
  updateCredentialConfiguration
        |
        v
GoogleAdsProvider                                extended adapter
  discoverAssets / validateAssetSelection
        |
        v
GoogleAdsClient                                  extended HTTP client
  listCustomers / getCustomer /
  listConversionActions / search
        |
        v
Google Ads API
  customers:listAccessibleCustomers
  customers/{id}/googleAds:search (GAQL)
```

`GoogleAdsProvider` now implements the optional
`MarketingProviderAssetDiscoveryInterface` (P7C.3), exactly as the Meta
provider does. `MarketingProviderService::supportsAssetDiscovery()` detects the
interface, so the Integration Management page and the generic
`integrations.assets.save` / `integrations.assets.refresh` routes light up for
Google with zero controller or route changes.

Declared capabilities are now:

- `oauth`
- `token_refresh`
- `asset_discovery`

## GoogleAdsClient Additions

Provider communication only; no business logic, no persistence:

- `listCustomers(accessToken)` — resolves
  `customers:listAccessibleCustomers` resource names to plain customer IDs.
- `getCustomer(customerId, accessToken)` — GAQL query for
  `customer.id`, `customer.descriptive_name`, `customer.currency_code`,
  `customer.time_zone`, `customer.manager`.
- `listConversionActions(customerId, accessToken)` — GAQL query for
  `conversion_action.id`, `name`, `category`, `type`, `status`,
  `primary_for_goal`.
- `search(customerId, accessToken, query)` — shared paginated
  `googleAds:search` helper following `nextPageToken` up to a bounded page
  count (10 pages).

All requests carry the tenant bearer token plus the platform developer token
and reuse the existing normalized, bounded error extraction.

## Google Ads Asset Model

Discovery returns provider asset DTOs (never persisted wholesale):

Customer accounts:

- `id` (customer_id)
- `descriptive_name`
- `currency_code`
- `time_zone`
- `manager` (boolean)
- `accessible` (boolean; false when the account soft-failed metadata queries)

Conversion actions:

- `id` (conversion_action_id)
- `customer_id` (owning account)
- `name`
- `category`
- `type`
- `status`
- `primary_for_goal` (boolean)
- `active` (derived: status is `ENABLED` or unknown)
- `missing` (true only for previously selected actions no longer discovered)

Conversion actions are only queried for accessible non-manager accounts;
manager accounts hold no uploadable conversion actions of their own. No
campaigns, ad groups, or audiences are discovered.

## Configuration Lifecycle and Selection Persistence

Administrator selections persist in `MarketingProviderCredential.configuration`
(the same JSON column Meta uses):

```json
{
  "customer_id": "1112223333",
  "conversion_action_ids": ["900001", "900002"]
}
```

Write path (unchanged platform code):

1. `IntegrationController::saveAssets` builds the selection from
   `SaveMarketingProviderAssetsRequest`.
2. `MarketingProviderService::saveAssetConfiguration` requires a connected (or
   expired) provider and delegates verification to the adapter.
3. `GoogleAdsProvider::validateAssetSelection` re-discovers live assets and
   rejects:
   - customer accounts not accessible to the connection,
   - conversion actions selected without a customer account,
   - conversion actions that do not exist under the selected customer,
   - previously removed (missing) conversion actions.
4. `MarketingProviderService::updateCredentialConfiguration` persists the
   sanitized payload in a transaction without touching encrypted tokens.

Client-submitted IDs are never trusted without adapter verification against a
live discovery, mirroring Meta. A failed validation throws before persistence,
so existing configuration is never corrupted.

Selections become the source of truth for future offline conversion uploads
(Phase 7D.3).

## Refresh Behavior

`Refresh Assets` posts to the existing generic `integrations.assets.refresh`
route:

- Google is re-queried live; no discovery cache exists to invalidate.
- Configuration is never written during discovery, so valid selections are
  preserved automatically.
- Previously selected conversion actions that disappeared remotely are
  surfaced in the discovery result as `active: false`, `missing: true`,
  `status: REMOVED`; the UI shows them greyed out with a "Removed" badge and a
  disabled checkbox.
- Saved configuration is never silently deleted — removed IDs remain in
  `configuration` until an administrator saves a new selection.
- Discovery failures on page load are read-only; Refresh and Save update
  provider status on failure via the existing service transitions
  (`expired` / `disconnected` / `error`).

## Error Handling

All provider exceptions are normalized through `GoogleAdsClient`:

- Missing credentials → `disconnected`, discovery not attempted.
- Locally expired token → `expired`, no network call.
- Revoked/invalid tokens (`UNAUTHENTICATED`, invalid authentication
  credentials, `invalid_grant`, expired, revoked) → canonical `expired`.
- Quota failures (`RESOURCE_EXHAUSTED`) and other API errors → `error` with a
  bounded message.
- Malformed (non-JSON) responses → `error` with a normalized unknown-error
  message.
- Individual inaccessible customer accounts soft-fail: they appear as
  `accessible: false` without aborting discovery, cannot be selected, and do
  not block conversion actions from other accounts. Token errors still abort.

## Integration UI

`integrations/show.blade.php` renders asset sections based on which asset keys
the adapter returns (`customers` vs `businesses`) — capability-driven, with no
provider slug branching in the controller:

- Customer Account select (descriptive name, ID, manager flag, currency, time
  zone).
- Conversion Actions checkbox list (name, owning customer, category, type,
  primary-for-goal, active/removed badges).
- Save Selection and Refresh Assets reuse the existing generic forms/routes.

`SaveMarketingProviderAssetsRequest` gained `customer_id` and
`conversion_action_ids` validation rules alongside the Meta fields; adapters
read only the keys they understand and return provider-specific sanitized
payloads.

## Multi-Tenancy

- Discovery always executes with the connected organization's encrypted
  credential; assets are never shared across organizations.
- Configuration writes go through the tenant-scoped credential row.
- Cross-tenant provider lookup remains impossible
  (`findProviderForOrganization` scoping unchanged).
- Covered by a dedicated tenant-isolation test.

## Provider Platform Reuse (Primary Success Metric)

Reused without any modification:

- `MarketingProviderAssetDiscoveryInterface` (P7C.3 optional contract)
- `MarketingProviderInterface` (P7C.1 frozen contract)
- `MarketingProviderService` (`discoverAssets`, `saveAssetConfiguration`,
  `updateCredentialConfiguration`, `supportsAssetDiscovery`, status
  transitions)
- `MarketingProviderRegistry`
- `MarketingProviderCredential` model and encrypted storage
- `IntegrationController` (show / saveAssets / refreshAssets)
- All routes (`integrations.assets.save`, `integrations.assets.refresh`)
- RBAC, tenant scoping, flash messaging
- Synchronization runtime (untouched)
- All migrations (no schema changes)

Changed (additive only):

- `GoogleAdsProvider` — implements asset discovery interface, adds
  `discoverAssets` / `validateAssetSelection`.
- `GoogleAdsClient` — adds `listCustomers`, `getCustomer`,
  `listConversionActions`, paginated `search`.
- `SaveMarketingProviderAssetsRequest` — additive Google fields.
- `resources/views/integrations/show.blade.php` — renders asset sections by
  returned asset keys; Google customer/conversion-action inputs added.

No provider platform redesign was required.

## Testing Summary

New suite `tests/Feature/GoogleAdsAssetDiscoveryTest.php` (14 tests):

- capability declaration and service detection
- customer + paginated conversion action discovery with metadata normalization
- empty accessible-customer list
- multiple accounts (client + manager; manager excluded from conversion
  actions)
- inaccessible account soft-fail and selection rejection
- save/update selection into configuration JSON (dedupe, token untouched)
- invalid selections rejected without corrupting configuration
- removed conversion actions surfaced inactive with configuration preserved
- expired token → `expired` without clearing configuration (no network call)
- revoked credentials → canonical `expired`
- quota and malformed responses → normalized `error`
- tenant isolation of configuration
- Integration UI shows Google assets, saves selection, hides tokens
- refresh re-queries Google and preserves selections
- marketing platform untouched by discovery

Updated:

- `tests/Feature/GoogleAdsProviderTest.php` — capabilities now include
  `asset_discovery`; out-of-scope assertions adjusted.

Suite results:

- Google Ads suites: 28 passed (180 assertions)
- Provider / Meta / Integration / Marketing / Metadata regression filter:
  275 passed (1226 assertions)
- Full suite: 689 tests, 2553 assertions, 0 failures
  (up from the 675-test / 2459-assertion quality gate; +14 tests)

## What Did Not Change

- Marketing Platform (tracking, attribution, conversions)
- Synchronization Runtime
- Meta provider behavior
- Provider platform contracts, routes, controllers, services, models
- Database schema (no new tables or columns)
- CRM, Revenue, Metadata Platform

## Completion

- Google Ads customer accounts are discovered with required metadata. Done.
- Conversion actions are discovered with required metadata. Done.
- Administrators select the active customer account. Done.
- Administrators select conversion actions. Done.
- Selections stored via existing Provider Platform configuration. Done.
- Refresh preserves valid selections and marks removed assets inactive. Done.
- Marketing Platform unchanged. Done.
- Synchronization Runtime untouched. Done.
- No provider platform redesign. Done.
- Comprehensive tests added. Done.
- Full suite passes with zero regressions. Done.

Phase 7D.3 (Google Offline Conversion Uploads) has not been started.
