# P7 Marketing Attribution Platform - Phase 7D.1 Impact Report

## Phase

Phase 7D.1 - Google Ads OAuth Foundation

## Outcome

Google Ads is now a concrete Provider Platform adapter with OAuth connection,
refresh, disconnect, and health behavior. The implementation is OAuth-only:
campaign discovery, customer hierarchy discovery, synchronization, reporting,
and conversion uploads remain unsupported.

The phase validated the provider-agnostic architecture. No frozen contract,
runtime service, controller, route, view, model, migration, or synchronization
runtime file required modification.

## OAuth Architecture

```text
Integration Management
        |
        v
MarketingProviderOAuthController          existing, unchanged
        |
        v
MarketingProviderService                  existing, unchanged write authority
        |
        v
MarketingProviderRegistry                 existing, unchanged
        |
        v
GoogleAdsProvider                         new adapter
        |
        v
GoogleAdsClient                           new HTTP client
        |
        +--> Google OAuth
        |
        +--> Google Ads API health endpoint
```

Generic routes are reused:

- `GET /marketing/providers/{provider}/connect`
- `GET /marketing/providers/{provider}/callback`
- `POST /marketing/providers/{provider}/disconnect`

OAuth uses Google's authorization-code flow with `access_type=offline` and
`prompt=consent` so a tenant refresh token is issued. State is encrypted,
expires after 15 minutes, and is bound to the provider row, organization, and
`google_ads` slug. The generic controller also compares state against the
session value.

## Provider Registration and Capabilities

`GoogleAdsProvider` is registered under `google_ads` through the existing
`config('marketing.providers.drivers')` map.

Declared capabilities:

- `oauth`
- `token_refresh`

No discovery, synchronization, webhook, reporting, or offline-conversion
capability is declared.

## GoogleAdsClient

The client contains Google communication only:

- authorization URL generation
- authorization-code token exchange
- refresh-token exchange
- token revocation
- token validation
- authenticated Google Ads API requests
- normalized, bounded error messages

The health request calls the read-only
`customers:listAccessibleCustomers` endpoint only to verify API reachability.
Returned customer resources are counted for diagnostics and are not persisted
or exposed as a discovery capability.

## Credential Lifecycle

All persistence continues through `MarketingProviderService`.

```text
OAuth callback
  -> GoogleAdsProvider returns normalized credentials
  -> MarketingProviderService::storeCredentials()
  -> MarketingProviderCredential encrypted casts
  -> provider status connected

Refresh
  -> stored tenant refresh token
  -> Google token endpoint
  -> MarketingProviderService replaces access token
  -> refresh token and configuration retained

Disconnect
  -> best-effort Google token revoke
  -> MarketingProviderService clears local credentials
  -> provider status disconnected
```

Stored fields:

- encrypted `access_token`
- encrypted `refresh_token`
- `expires_at`
- granted scopes
- token type
- nullable `configuration.customer_id`
- non-secret Google subject/email diagnostics

No Google-specific credential table or column was added.

## Health Model

`GoogleAdsProvider::reportHealth()` delegates to its `healthCheck()` behavior
and returns existing canonical Provider Health statuses.

| Condition | Status |
| --- | --- |
| No access token | `disconnected` |
| Local token expiry | `expired` |
| Revoked/invalid access token | `expired` |
| Missing refresh capability | `error` |
| Invalid developer/client configuration | `error` |
| Token valid and Google Ads API reachable | `connected` |
| Other Google API failure | `error` |

Health metadata reports refresh capability and API reachability without
returning tokens or customer identifiers.

## Platform and Tenant Configuration

Platform application configuration is environment-level:

- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `GOOGLE_REDIRECT_URI`
- `GOOGLE_ADS_DEVELOPER_TOKEN`
- optional API version, timeout, scope, and endpoint overrides

The developer token is platform-level because it identifies Konnect Nex's Google
Ads API application. Tenant access tokens, refresh tokens, and optional Google
Ads customer IDs remain in encrypted organization-owned database records.

## Security Considerations

- OAuth state is encrypted, time-limited, provider-bound, and tenant-bound.
- The controller performs a second session-state comparison.
- Access and refresh tokens use the existing encrypted Eloquent casts.
- Tokens remain hidden from model serialization and Integration Management.
- Google client errors are normalized and truncated; request credentials are
  never included in error messages.
- Remote revoke is best-effort; local disconnect always completes.
- A missing refresh token rejects the callback rather than creating a
  connection that cannot satisfy the declared lifecycle.
- Tenant customer IDs use provider-agnostic configuration JSON.

## Tenant Isolation

Every connection is the existing `(organization_id, slug)` provider row.
Credential writes, lookups, health checks, refresh, and disconnect actions all
operate on the current organization's resolved connection. Cross-tenant
provider IDs and credentials remain inaccessible through the existing
`BelongsToOrganization` and explicit service constraints.

## Integration Management

Google Ads already existed in the provider catalog. Registering the driver made
the existing card connectable automatically.

The unchanged generic UI provides:

- Connect / Reconnect
- Disconnect
- canonical connection status
- last health check
- credential expiry
- provider detail view without token exposure

No Google-specific page, route, controller action, or form was added.

## Provider Platform Reuse Analysis

This is the primary success metric for Phase 7D.1.

Reused without modification:

- `MarketingProviderInterface`
- every optional Provider Platform contract
- `MarketingProviderRegistry`
- `MarketingProviderService`
- `MarketingProviderOAuthController`
- `IntegrationController`
- generic OAuth and Integration Management routes
- Integration Management index and detail views
- `MarketingProvider` and `MarketingProviderCredential`
- encrypted credential schema and tenant scopes
- provider lifecycle and canonical statuses
- synchronization runtime contracts, service behavior, and persistence
- frozen Marketing Platform contracts and runtime

Additions:

- one Google Ads provider adapter
- one thin Google HTTP client
- one provider-specific feature test suite
- this impact report

Minimal registration/configuration edits:

- one driver entry and one Google platform-config block
- Google platform variables in `.env.example`
- one existing UI test expectation changed from catalog-only to connectable

Measured structurally, **100% of the existing Provider Platform lifecycle
infrastructure was reused without modification**. The only production edit
outside the two new Google classes is configuration registration. There were
zero architectural changes to the Provider Registry and zero changes to the
Synchronization Runtime.

## Testing Summary

New suite: `tests/Feature/GoogleAdsProviderTest.php`

Coverage includes:

- provider registration and capability boundary
- offline-consent authorization URL
- encrypted, tenant-bound, expiring state
- successful service and HTTP callback flows
- encrypted access/refresh storage
- nullable customer configuration
- denied consent
- invalid state rejection at controller and adapter layers
- invalid redirect/token exchange normalization
- successful token refresh and refresh-token retention
- revoked refresh-token failure
- connected, expired, and revoked health paths
- Google Ads API reachability
- remote revoke and local disconnect
- tenant isolation and UI token secrecy
- explicit rejection of out-of-scope operations

Targeted Google Ads suite:

- **14 passed (86 assertions)**

Final quality gate:

- Provider + Meta + Google Ads + Integration: **133 passed (678 assertions)**
- Marketing suite: **141 passed (586 assertions)**
- Full suite: **675 passed (2459 assertions)**
- Formatting: Pint passed
- Failures: **0**

Quality gate delta from Phase 7C.9: **+14 tests, +86 assertions**.

## What Did Not Change

- Frozen Marketing Platform contracts and write authorities
- Frozen Provider Platform contracts
- Provider Registry architecture
- Synchronization Runtime
- Meta provider behavior
- CRM, Revenue, and Metadata Platform
- No campaign/customer discovery
- No synchronization jobs, queues, workers, or schedules
- No reporting or conversion upload implementation

## Completion

Phase 7D.1 is complete at the implementation level. Google Ads is another
registered provider using the same tenant credential, OAuth, status, health,
disconnect, and Integration Management lifecycle as Meta. Phase 7D.2 Campaign
Discovery has not been started.
