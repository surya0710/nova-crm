# P7 Marketing Attribution Platform - Phase 7C.3 Impact Report

## Phase

Phase 7C.3 - Meta Business Asset Discovery

## What Changed?

Implemented **read-only Meta Business asset discovery** and **explicit tenant asset selection** after OAuth connect. No Lead Ads import, webhooks, campaign sync, or Marketing Platform writes.

### Meta Graph discovery

- Extended `App\Services\Marketing\Providers\MetaGraphClient` with paginated, read-only Graph helpers:
  - Business Managers — `GET /me/businesses`
  - Ad Accounts — `GET /{business-id}/owned_ad_accounts` + `client_ad_accounts`
  - Pages — `GET /{business-id}/owned_pages` + `client_pages` (fallback `GET /me/accounts`)
  - Pixels — `GET /{business-id}/owned_pixels` + `GET /act_{id}/adspixels`
  - Lead Forms — `GET /{page-id}/leadgen_forms`
- Soft-fails inaccessible edges (deleted/inaccessible objects) without aborting the whole discovery, except expired/revoked token errors which surface to the caller.

### MetaMarketingProvider

- Declares capabilities: `oauth`, `asset_discovery`.
- Implements optional `MarketingProviderAssetDiscoveryInterface`:
  - `discoverAssets()` — normalizes Graph payloads into provider-agnostic asset lists
  - `validateAssetSelection()` — re-discovers and rejects IDs not available to the connection
- Still returns “Not yet implemented” for synchronize / webhooks / offline conversions.
- **Does not persist** Eloquent models.

### Contract approach

- **Did not modify** frozen `MarketingProviderInterface` (P7C.1).
- Added additive optional contract: `App\Contracts\MarketingProviderAssetDiscoveryInterface`.
- `MarketingProviderService` resolves discovery via `instanceof` — no Meta special-case branches.

### MarketingProviderService (single write authority)

- `discoverAssets($provider, $options, $updateStatusOnFailure = true)`
- `saveAssetConfiguration($provider, $selection)` — validate then persist
- `updateCredentialConfiguration($provider, $configuration)` — replaces JSON only; **tokens untouched**
- `supportsAssetDiscovery($provider)`
- On discovery failure (refresh/save paths): updates status to `expired` / `error` / `disconnected` **without clearing** existing `configuration`.
- Integration detail page load peeks assets with `$updateStatusOnFailure = false` so a transient Graph outage does not clobber connection status while browsing.

### Configuration storage

Selected assets live only in `MarketingProviderCredential.configuration`:

```json
{
  "business_id": "...",
  "ad_account_id": "...",
  "page_id": "...",
  "pixel_id": "...",
  "lead_form_ids": ["...", "..."]
}
```

No provider-specific database columns. Duplicate lead form IDs are deduplicated on save.

### Integration Management UI

Extended `integrations/show` for providers that support asset discovery:

- Lists Business Managers, Ad Accounts, Pages, Pixels, Lead Forms (friendly names)
- Select / Save / Update selections
- Refresh Assets (re-queries Meta; keeps prior selections when still available)
- Saved selections summary (IDs only — never tokens)
- Routes:
  - `POST integrations/{provider}/assets` → `integrations.assets.save`
  - `POST integrations/{provider}/assets/refresh` → `integrations.assets.refresh`

### OAuth scopes (platform env)

Default `META_OAUTH_SCOPES` expanded for discovery:

`business_management,ads_read,pages_show_list,pages_read_engagement,leads_retrieval`

Existing connections may need **Reconnect** to grant new scopes. Documented in `.env.example`.

### What did not change

- Frozen Marketing Platform contracts and runtime services
- Provider registry resolution model
- Credential encryption / OAuth foundation from 7C.2
- No Lead Ads import, webhook registration/processing, campaign sync, offline conversions, audiences, reporting, or automation

## Architecture

```
Browser (Integrations → Meta details)
  → IntegrationController
      → MarketingProviderService          ← single write authority
          → MarketingProviderAssetDiscoveryInterface
              → MetaMarketingProvider
                  → MetaGraphClient (read-only Graph)
          → MarketingProviderCredential.configuration
```

Marketing Platform remains unaware of Meta assets.

## Asset Selection Lifecycle

```
Connect (7C.2 OAuth)
  → Discover assets (Graph read-only)
  → Admin selects business / ad account / page / pixel / lead forms
  → validateAssetSelection (live Graph verification)
  → updateCredentialConfiguration (JSON replace)
  → Refresh Assets re-queries; prior selections remain when still present
  → Failed discovery/refresh updates status; configuration retained
```

## Graph API Endpoints Used

| Asset | Endpoint(s) |
| --- | --- |
| Business Managers | `/me/businesses` |
| Ad Accounts | `/{business-id}/owned_ad_accounts`, `/{business-id}/client_ad_accounts` |
| Pages | `/{business-id}/owned_pages`, `/{business-id}/client_pages`, `/me/accounts` |
| Pixels | `/{business-id}/owned_pixels`, `/act_{id}/adspixels` |
| Lead Forms | `/{page-id}/leadgen_forms` |

## Security Considerations

- Client-submitted asset IDs are never trusted without Graph verification.
- Tokens remain encrypted and hidden from UI/serialization.
- Organization ownership enforced via tenant context + `BelongsToOrganization`.
- Cross-tenant provider IDs cannot be resolved or updated.
- Expired / revoked tokens mark provider status without wiping saved configuration.
- Discovery is read-only — no webhook subscriptions created.

## Multi-Tenant Behavior

Each organization has an independent Meta connection and independent `configuration` JSON. Selections never leak across tenants. Lookups always constrain by `organization_id`.

## Testing Summary

New suite: `tests/Feature/MetaAssetDiscoveryTest.php`

Coverage includes:

- Business / ad account / page / pixel / lead form discovery
- Save + update selections; duplicate lead form dedupe
- Invalid / unauthorized asset rejection without corrupting prior config
- Expired token status update with configuration retention
- Tenant isolation + cross-tenant HTTP protection
- Integrations UI discover / save / refresh
- Marketing Platform visitor table untouched

Regression suites (all green):

| Suite | Result |
| --- | --- |
| Provider + Meta (`MarketingProviderPlatformTest`, `MetaMarketingProviderTest`, `MetaAssetDiscoveryTest`) | Passed |
| Marketing filter | **130 passed (524 assertions)** |
| Full suite | **596 passed (2033 assertions)**, 0 failures |

## CTO Recommendation

Meta Asset Discovery is complete. Proceed to Phase 7C.4 (Meta Lead Ads Integration) only after review. Do not register webhooks or import leads in this phase.
