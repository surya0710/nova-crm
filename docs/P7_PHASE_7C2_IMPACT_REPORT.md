# P7 Marketing Attribution Platform - Phase 7C.2 Impact Report

## Phase

Phase 7C.2 - Meta Business Provider Foundation

## What Changed?

Implemented Meta as the first concrete `MarketingProviderInterface` adapter, focused exclusively on OAuth and provider lifecycle.

### Meta adapter

- `App\Services\Marketing\Providers\MetaMarketingProvider` — slug `meta`, capability `oauth` only.
- `App\Services\Marketing\Providers\MetaGraphClient` — Graph HTTP helper (code exchange, long-lived token exchange, `/me` health, permission revoke).
- Registered via `config('marketing.providers.drivers.meta')` — registry resolves Meta with **no special-case logic**.

### OAuth foundation

- `authorize(phase=start)` → Facebook Login dialog URL + signed encrypted `state`.
- `authorize(phase=callback)` → code → short-lived token → long-lived token → `/me` identity → credential payload.
- Thin provider-agnostic HTTP endpoints:
  - `GET /marketing/providers/{provider}/connect`
  - `GET /marketing/providers/{provider}/callback`
  - `POST /marketing/providers/{provider}/disconnect`
- Controller: `MarketingProviderOAuthController` (uses `MarketingProviderService` only).

### Credential lifecycle

- Connect / reconnect / disconnect / refresh / expiry all persist through `MarketingProviderService`.
- Meta long-lived user tokens have no separate refresh_token; refresh re-exchanges via `fb_exchange_token`.
- Tokens encrypted at rest (`encrypted` casts); secrets hidden from serialization.

### Health

- `reportHealth` calls Graph `/me` with stored token.
- Maps to canonical statuses: `connected`, `disconnected`, `expired`, `error`.
- Small provider-agnostic fix: `MarketingProviderService::checkHealth` now honors adapter-suggested `disconnected` (does not force `error`).

### Configuration (env-driven)

| Env | Purpose |
| --- | --- |
| `META_APP_ID` | App ID |
| `META_APP_SECRET` | App secret |
| `META_REDIRECT_URI` | Optional; defaults to named callback route |
| `META_GRAPH_API_VERSION` | Default `v21.0` |
| `META_HTTP_TIMEOUT` | Default `15` |
| `META_OAUTH_SCOPES` | Default `business_management,ads_read` |

Documented in `.env.example`.

### Explicitly unsupported (interface satisfied)

- `synchronize` / `receiveWebhook` / `uploadConversions` return `{ ok: false, message: "Not yet implemented: …" }`.

### What did not change

- Frozen Marketing Platform contracts and runtime services (tracking, classification, attribution, conversions, backfill).
- No Lead Ads, webhooks, campaign sync, offline conversions, audiences, insights, pages, pixels, forms, reporting, or automation.

## Architecture

```
Browser
  → MarketingProviderOAuthController
      → MarketingProviderService          ← single write authority
          → MarketingProviderRegistry
              → MetaMarketingProvider
                  → MetaGraphClient (Graph API)
```

Meta is interchangeable with any future adapter. No Meta branches in the registry or provider service.

## OAuth Lifecycle

```
registerProvider(org, meta)
        → authorize(phase=start) → redirect to Facebook dialog
        → callback(code, state)
        → authorize(phase=callback)
        → storeCredentials (encrypted, connected)
        → optional refreshCredentials (fb_exchange_token)
        → disconnect → revoke (best-effort) + clearCredentials
```

State is Crypt-signed and bound to `provider_id` + `organization_id` + expiry; session double-checks on HTTP callback.

## Credential Lifecycle

| Action | Resulting status |
| --- | --- |
| Successful callback / refresh | `connected` |
| Stored `expires_at` in the past | `expired` |
| Disconnect / clear | `disconnected` |
| Health/API failure | `error` (or `expired` when token-expiry shaped) |

## Health Model

| Condition | Status |
| --- | --- |
| No credentials | `disconnected` |
| Local `expires_at` passed | `expired` |
| `/me` succeeds | `connected` |
| Graph error (#190 / expired language) | `expired` |
| Other Graph failure | `error` |

Health checks never write Marketing Platform visitor/touch/attribution/conversion tables.

## Security Considerations

- App secret only used server-side in Graph exchanges.
- Access tokens encrypted at rest; `$hidden` on credential model.
- OAuth `state` encrypted + session compared with `hash_equals`.
- Tenant isolation via `organization_id` + `BelongsToOrganization`.
- Best-effort remote revoke; local disconnect always completes.
- Callback failures mark provider `error` without leaking tokens to logs (message only).

## Testing Summary

- Provider + Meta filter: **34 passed (150 assertions)**.
- Marketing suite: **129 passed (522 assertions)**.
- Full suite: **576 passed (1931 assertions)**, 0 failures.

Coverage includes registry resolution, OAuth URL generation, callback encryption, HTTP connect/callback/disconnect, token refresh, reconnect, health connected/expired/error, unsupported capabilities, tenant isolation, and Marketing Platform regression.

## CTO Recommendation

Meta Business Provider Foundation (OAuth + lifecycle) is complete. Proceed to the next Meta sub-phase (e.g. Lead Ads or webhooks) only after review. Do not expand sync/conversion capabilities in this phase.
