# P7 Marketing Attribution Platform - Phase 7C.1 Impact Report

## Phase

Phase 7C.1 - Provider Integration Foundation

## What Changed?

Added a reusable, provider-agnostic **Provider Platform** that sits beside (and consumes) the frozen Marketing Platform. No Meta, Google, LinkedIn, or other vendor SDK/API code was introduced.

### Provider contracts

- `App\Contracts\MarketingProviderInterface` — authorize, refresh credentials, revoke, synchronize, receive webhooks, upload conversions, report health, plus slug/name/capabilities.
- Adapters return normalized arrays; they must not persist Eloquent models or write Marketing Platform tables.

### Registry

- `App\Services\Marketing\Providers\MarketingProviderRegistry` — register / resolve / has / slugs / all / supported.
- Bound as a singleton in `AppServiceProvider`; drivers loaded from `config('marketing.providers.drivers')` (empty in this phase).

### Credential & connection models

- Additive migrations:
  - `marketing_providers` — tenant connection row (`organization_id`, `slug`, `status`, capabilities, health/sync timestamps).
  - `marketing_provider_credentials` — encrypted `access_token` / `refresh_token`, refresh/expiry/scopes.
- Models: `MarketingProvider`, `MarketingProviderCredential` (`BelongsToOrganization`).
- Canonical statuses: `connected`, `disconnected`, `expired`, `error`.
- Factories for both models.

### Provider service (single write authority)

- `MarketingProviderService` owns all writes to provider + credential tables:
  - register / find / list connections
  - store / clear credentials (encryption via Eloquent casts)
  - disconnect (best-effort adapter revoke + local clear)
  - health state transitions
  - checkHealth / authorize / refreshCredentials / synchronize orchestration
  - webhook + conversion upload delegation
  - catalog + registered provider discovery

### Config

- New `config/marketing.php` → `providers` section: statuses, future catalog (`meta`, `google_ads`, `linkedin`, `microsoft_ads`, `tiktok`), empty `drivers` map.

### Tests

- `tests/Support/FakeMarketingProvider.php` — test-only adapter (not a production provider).
- `tests/Feature/MarketingProviderPlatformTest.php` — registry, resolution, credential lifecycle, encryption, tenant isolation, health transitions, orchestration, Marketing Platform regression.

### Documentation

- This impact report. Frozen Marketing Platform contracts were **not** modified.

## Architecture

```
Marketing Platform (frozen SoT)
        ↑ read conversions / attribution later
Provider Platform (this phase)
        ↓
MarketingProviderRegistry
        ↓
MarketingProviderInterface adapters (none in production yet)
        ↓ future
Meta | Google Ads | LinkedIn | …
```

```
MarketingProviderService          ← single write authority
        ↓
marketing_providers
marketing_provider_credentials    ← encrypted secrets
```

- Controllers / OAuth routes / webhooks: not added.
- Marketing tracking, classification, attribution, conversion, and backfill services: unchanged.

## Credential Model

| Field | Storage |
| --- | --- |
| `access_token` | Eloquent `encrypted` cast, `$hidden` |
| `refresh_token` | Eloquent `encrypted` cast, `$hidden` |
| `expires_at` | timestamp |
| `scopes` | JSON |
| `token_type` | string |
| `organization_id` | tenant ownership + cascade |

One credential row per provider connection (`unique(marketing_provider_id)`).

Storing non-expired credentials → `connected`. Storing expired credentials → `expired`. Clearing / disconnect → `disconnected`.

## Registry

| Operation | Behavior |
| --- | --- |
| `register(adapter)` | Bind by `slug()` |
| `resolve(slug)` | Return adapter or throw |
| `supported()` | `[{slug, name, capabilities}, …]` |
| Config `drivers` | Class map loaded at container boot |

Adding a future provider = one adapter class + one `drivers` entry (+ optional catalog row). No schema change.

## Extension Points (hooks, not implemented)

| Capability | Interface method | Intended use |
| --- | --- | --- |
| OAuth | `authorize` / `refreshCredentials` / `revoke` | Connect accounts; token lifecycle |
| Campaign Sync | `synchronize` | Normalized accounts/campaigns DTOs → future sync persistence |
| Webhooks | `receiveWebhook` | Signature validation + normalize; route into lead intake later |
| Offline Conversions | `uploadConversions` | Push `MarketingConversion` payloads to provider APIs |
| Audience Sync | declared in `capabilities()` | Future; no method until a contract revision |
| Health | `reportHealth` | Drive `connected` / `expired` / `error` |

Adapters declare capabilities; callers must not assume undeclared capabilities.

## Explicitly Deferred

- Meta OAuth / Graph API / webhooks / Lead Ads
- Google Ads / LinkedIn / Microsoft / TikTok APIs
- Campaign hierarchy tables & sync persistence
- Offline conversion upload jobs
- Provider UI, RBAC permissions, REST endpoints

## Testing Summary

- `php artisan test --filter=MarketingProvider` — **20 passed (82 assertions)**.
- `php artisan test --filter=Marketing` — **115 passed (454 assertions)** (prior 95 + 20 provider).
- `php artisan test` (full suite) — **562 passed (1863 assertions)**, 0 failures.

Coverage includes:

- Registry register/resolve/supported; unknown slug rejection
- Empty production drivers; catalog still lists future providers
- Provider registration idempotency; disconnected default
- Credential encrypt-at-rest; connected vs expired transitions
- Disconnect clears credentials and best-effort revokes
- Canonical status transitions; invalid status rejected
- Health check updates status from adapter
- Authorize/refresh persist via service
- Synchronize success/failure orchestration
- Webhook + upload delegation
- Tenant isolation across organizations
- Secrets hidden from array serialization
- Marketing tracking regression (no provider coupling)

## Did Any Architectural Assumptions Change?

No. Provider Platform is additive. Marketing Platform remains the attribution/conversion source of truth. Providers consume it; they do not redefine it.

## Architectural Correction (Tenant Configuration)

Later review clarified that **platform** app credentials (e.g. `META_APP_ID`) belong in `.env`, while **tenant** access tokens and account configuration belong only in encrypted DB rows per organization.

See `docs/P7_PROVIDER_PLATFORM_TENANT_CONFIGURATION.md` and the Integration Management UI (`/integrations`). `configuration` JSON on credentials holds provider-agnostic account identifiers without schema redesign.

## CTO Recommendation

Provider Integration Foundation is complete. Proceed to **Phase 7C.2 — Meta Business Integration** (first concrete `MarketingProviderInterface` adapter + OAuth), without changing Marketing Platform runtime contracts. Always persist tenant tokens via `MarketingProviderService`, never via `.env`.
