# NovaCRM Phase 7C.2 — ChatGPT Development Brief

> **Purpose of this document:** Give ChatGPT (or any reviewer) a complete, accurate explanation of what Phase 7C.2 built, why it was designed this way, what is in scope vs out of scope, and how the pieces fit together. Use this as context before answering questions, reviewing code, planning Phase 7C.3, or writing follow-up prompts.

---

## One-sentence summary

Phase 7C.2 adds **Meta Business OAuth only**: an organization can securely connect, reconnect, disconnect, and health-check a Meta account through the existing Provider Platform — with **no** Lead Ads, webhooks, campaign sync, or conversion uploads.

---

## Product / platform context

NovaCRM has two related but separate platforms:

| Layer | Role | Status |
| --- | --- | --- |
| **Marketing Platform** | Source of truth for visitors, sessions, touches, channel classification, attribution, conversions, backfill | **Frozen** (P7B.F) |
| **Provider Platform** | Vendor adapters (Meta, Google, LinkedIn, …) that authorize and later sync/export | Built in P7C.1; Meta OAuth in **P7C.2** |

Hard rule: Meta must remain a **Provider Platform adapter**. Meta-specific logic must not leak into:

- Marketing Platform services / contracts
- `MarketingProviderRegistry`
- `MarketingProviderService` (orchestration + persistence only)

Layering:

```
Marketing Platform (frozen SoT)
        ↓ (future read/export only)
Provider Platform
        ↓
MarketingProviderInterface
        ↓
MetaMarketingProvider  ← this phase
        ↓
MetaGraphClient (Graph HTTP)
```

---

## What Phase 7C.1 already provided (do not rebuild)

Before 7C.2, the foundation already existed:

- `MarketingProviderInterface` — authorize, refreshCredentials, revoke, synchronize, receiveWebhook, uploadConversions, reportHealth, plus slug/name/capabilities
- `MarketingProviderRegistry` — config-driven driver resolution (`config('marketing.providers.drivers')`)
- `MarketingProvider` + `MarketingProviderCredential` models (tenant-scoped, encrypted tokens)
- `MarketingProviderService` — **single write authority** for provider rows and credentials
- Integration Management UI shell + catalog
- Fake provider for tests

7C.2 **plugs Meta into that foundation**; it does not invent a parallel credential store or Meta-only tables.

---

## What Phase 7C.2 implemented

### 1. Meta adapter

**Class:** `App\Services\Marketing\Providers\MetaMarketingProvider`  
**Slug:** `meta`  
**Capability declared:** `oauth` only

| Interface method | Behavior in 7C.2 |
| --- | --- |
| `authorize(phase=start)` | Build Facebook Login dialog URL + signed encrypted OAuth `state` |
| `authorize(phase=callback)` | Validate state → exchange code → long-lived token → `/me` identity → credential payload |
| `refreshCredentials` | Re-exchange access token via Meta `fb_exchange_token` (Meta has no separate refresh_token for long-lived user tokens) |
| `revoke` | Best-effort Graph permission revoke; local disconnect always proceeds in the service |
| `reportHealth` | Local expiry check + Graph `/me`; maps to connected / disconnected / expired / error |
| `synchronize` | Returns `{ ok: false, message: "Not yet implemented: …" }` |
| `receiveWebhook` | Same — not implemented |
| `uploadConversions` | Same — not implemented |

**HTTP helper:** `App\Services\Marketing\Providers\MetaGraphClient`  
Handles code exchange, long-lived token exchange, `/me`, permission revoke. No marketing data sync.

**Registration:** `config('marketing.providers.drivers.meta')` → `MetaMarketingProvider::class`  
Registry resolves Meta like any other driver — **no special-case Meta branches**.

### 2. OAuth HTTP surface (provider-agnostic)

**Controller:** `App\Http\Controllers\MarketingProviderOAuthController`

| Route | Action |
| --- | --- |
| `GET /marketing/providers/{provider}/connect` | Register connection → `authorize(start)` → redirect to provider dialog |
| `GET /marketing/providers/{provider}/callback` | Validate session state → `authorize(callback)` → store credentials via service |
| `POST /marketing/providers/{provider}/disconnect` | Service disconnect (revoke + clear local credentials) |

Controller talks only to `MarketingProviderService` + tenant context. It never writes credentials directly and never calls Graph APIs itself.

### 3. Credential persistence (service-owned)

All writes go through `MarketingProviderService`:

- Encrypted `access_token` (Eloquent `encrypted` cast)
- `expires_at`
- Connection status + connected-at timestamps
- Optional metadata in existing JSON fields (e.g. Meta user id/name)
- `refresh_token` stored only if a provider supplies one (Meta does not for this flow)

**No Meta-specific database columns.** One org → one Meta provider connection; reconnect replaces credentials safely.

### 4. Connection lifecycle statuses

Canonical statuses (service-owned):

- `connecting` (where used by UI/orchestration)
- `connected`
- `expired`
- `error`
- `disconnected`

Health mapping (Meta):

| Condition | Status |
| --- | --- |
| No credentials | `disconnected` |
| Local `expires_at` in the past | `expired` |
| Graph `/me` succeeds | `connected` |
| Graph error shaped like token expiry (#190 / expired language) | `expired` |
| Other Graph failure | `error` |

### 5. Integration Management UI

Existing Integrations screens were enabled for Meta:

- Connect / Reconnect (link to OAuth connect route)
- Disconnect (form POST)
- View operational status (never tokens or secrets)

Views: `resources/views/integrations/index.blade.php`, `show.blade.php`

### 6. Application configuration (env only)

Tenant tokens never go in `.env`. App-level Meta app credentials do:

| Env | Purpose |
| --- | --- |
| `META_APP_ID` | App ID |
| `META_APP_SECRET` | App secret (server-side only) |
| `META_REDIRECT_URI` | Optional; defaults to named callback route |
| `META_GRAPH_API_VERSION` | Default `v21.0` |
| `META_HTTP_TIMEOUT` | Default `15` |
| `META_OAUTH_SCOPES` | Default `business_management,ads_read` |

Wired in `config/marketing.php` under `providers.meta`. Documented in `.env.example`.

---

## OAuth flow (end-to-end)

```
User clicks Connect on Integrations UI
  → GET /marketing/providers/meta/connect
  → MarketingProviderService::registerProvider(org, meta)
  → MetaMarketingProvider::authorize(phase=start)
       • Crypt-encrypt state { provider_id, organization_id, slug, exp }
       • Return Facebook dialog URL
  → Session stores state for double-check
  → Browser redirects to Facebook Login

User approves on Meta
  → GET /marketing/providers/meta/callback?code=…&state=…
  → Session state compared with hash_equals
  → MetaMarketingProvider::authorize(phase=callback)
       • Decrypt/validate state (org + provider + expiry)
       • Code → short-lived token
       • Short → long-lived token (fb_exchange_token)
       • GET /me for external account identity
       • Return normalized credential array
  → MarketingProviderService::storeCredentials → status connected

Disconnect
  → MetaMarketingProvider::revoke (best-effort)
  → Service clearCredentials → status disconnected
```

Reconnect = same connect path; previous credentials are replaced safely through the service.

---

## Security model (must not regress)

1. **CSRF-safe OAuth state** — encrypted payload bound to provider + organization + 15-minute expiry; session `hash_equals` on callback.
2. **Encrypted credential storage** — tokens never returned in UI/API serialization (`$hidden` on credential model).
3. **Organization ownership** — `BelongsToOrganization` + tenant context on connect/callback/disconnect.
4. **Expired token detection** — local `expires_at` + Graph health mapping.
5. **Never log** access tokens, authorization codes, or client secrets (log message strings / ids only).
6. **App secret** used only server-side in Graph exchanges.

---

## Explicitly out of scope (Phase 7C.3+)

Do **not** treat these as missing bugs of 7C.2:

- Lead Ads import
- Campaign / ad account / business discovery sync
- Webhooks
- Offline conversion upload
- Audience sync
- Reporting / automation
- Any writes into Marketing Platform visitor/touch/attribution/conversion tables from Meta

Interface methods for sync/webhook/conversions return documented “Not yet implemented” responses on purpose.

---

## Key files (implementation map)

```
app/Contracts/MarketingProviderInterface.php          # frozen contract from 7C.1
app/Services/MarketingProviderService.php             # single write authority
app/Services/Marketing/Providers/MarketingProviderRegistry.php
app/Services/Marketing/Providers/MetaMarketingProvider.php
app/Services/Marketing/Providers/MetaGraphClient.php
app/Http/Controllers/MarketingProviderOAuthController.php
app/Models/MarketingProvider.php
app/Models/MarketingProviderCredential.php
config/marketing.php
routes/web.php                                        # connect / callback / disconnect
resources/views/integrations/*.blade.php
tests/Feature/MetaMarketingProviderTest.php
tests/Feature/MarketingProviderPlatformTest.php
docs/P7_PHASE_7C2_IMPACT_REPORT.md
```

---

## Testing expectations

Coverage areas:

- Provider registration & registry resolution for Meta
- Authorization URL generation
- OAuth state validation (match / mismatch / expired / invalid)
- Callback success & failure (missing code, denied error)
- Connect / reconnect / disconnect lifecycle
- Encrypted credential storage
- Expired credentials / health statuses
- Organization isolation / cross-tenant protection
- Marketing Platform + CRM regression (no behavioral changes)

Suites to run when validating:

1. Provider / Meta suite  
2. Marketing suite  
3. Full suite — zero failures required  

Documented gate at 7C.2 completion (see impact report): provider + Meta, marketing, and full suite all green.

---

## Architectural invariants (for any future ChatGPT work)

When suggesting or writing code after this phase:

1. **Do not modify frozen Marketing Platform contracts** unless a genuine architectural flaw is found.
2. **Persistence only via `MarketingProviderService`** — adapters return arrays; they do not save Eloquent credentials themselves.
3. **No Meta branches in registry or provider service** — config drivers only.
4. **No provider-specific DB columns** for Meta — use encrypted credentials + configuration JSON.
5. **Stop at OAuth foundation** until a new phase prompt explicitly opens Lead Ads / webhooks / conversions.
6. **Never put tenant Meta tokens in `.env`** — only app-level `META_APP_*`.

---

## How this phase relates to the next one

| Phase | Intent |
| --- | --- |
| **7C.1** | Provider Platform foundation (interface, registry, credentials, service) |
| **7C.2** | Meta OAuth foundation (this brief) |
| **7C.3** | Meta Lead Ads integration (not started; do not begin from this brief alone) |

Acceptance for 7C.2 is met when organizations can securely connect/disconnect Meta, credentials are encrypted per org, UI supports connect/reconnect/disconnect/status, Marketing Platform is untouched, and tests pass.

---

## Suggested ChatGPT usage

Paste this brief (and optionally `docs/P7_PHASE_7C2_IMPACT_REPORT.md`) when you want ChatGPT to:

- Explain how Meta connect works to engineers or stakeholders
- Review a PR for architectural leakage into Marketing Platform
- Plan Phase 7C.3 without breaking OAuth / credential ownership rules
- Debug OAuth state, token encryption, or tenant isolation issues
- Write additional tests without inventing out-of-scope sync features

**Instruction to ChatGPT:** Treat Marketing Platform and Provider Platform contracts as frozen. Treat Meta as an interchangeable adapter behind `MarketingProviderInterface`. Prefer extending `MetaMarketingProvider` / `MetaGraphClient` for Meta features; never teach Marketing Platform services about Facebook Graph APIs.
`)
