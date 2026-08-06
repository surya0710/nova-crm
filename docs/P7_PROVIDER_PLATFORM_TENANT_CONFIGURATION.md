# Provider Platform — Tenant Configuration Architecture

## Status

- Architectural correction (pre–provider OAuth expansion)
- Applies to Marketing providers and future Konnect Nex integration modules

## Principle

Separate **platform configuration** from **tenant configuration**.

```
.env / config          →  Konnect Nex's applications at Meta, Google, …
Database (per org)     →  Each customer's connected accounts & tokens
```

A SaaS instance must never share one customer Meta/Google account across all tenants.

## Platform Credentials (.env)

Identify Konnect Nex's registered applications for an environment (dev / staging / production).

Examples:

| Variable | Meaning |
| --- | --- |
| `META_APP_ID` / `META_APP_SECRET` | Konnect Nex Meta app |
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` | Future Google app |
| `LINKEDIN_CLIENT_ID` / `LINKEDIN_CLIENT_SECRET` | Future LinkedIn app |

Rules:

- One set per **environment**, not per organization.
- Never store customer access tokens, refresh tokens, business IDs, or ad account IDs in `.env`.
- Read via `config/marketing.php` (or future `config/services.php` / module config).

## Tenant Credentials (Database)

Owned by an organization. Written only through `MarketingProviderService`.

### `marketing_providers`

Connection row per `(organization_id, slug)`:

- `status` — `connected` | `disconnected` | `expired` | `error`
- `external_account_id` — opaque external identity (provider-agnostic)
- `connected_at` / `disconnected_at` / `last_health_at` / `last_error`
- `capabilities` / `metadata` — JSON, no provider-specific columns

### `marketing_provider_credentials`

Encrypted secrets + tenant configuration:

| Field | Purpose |
| --- | --- |
| `access_token` | Encrypted |
| `refresh_token` | Encrypted (nullable; Meta long-lived may omit) |
| `expires_at` | Token expiry |
| `scopes` | JSON |
| `configuration` | JSON bag for business_id, ad_account_id, pixel_id, etc. |
| `metadata` | Non-secret diagnostics |

No `meta_*` or `google_*` columns. Future providers extend `configuration` keys only.

## Multi-Tenant Isolation

```
Org A  →  marketing_providers (slug=meta)  →  credentials A
Org B  →  marketing_providers (slug=meta)  →  credentials B
```

- `BelongsToOrganization` + unique `(organization_id, slug)`
- Lookups always constrain by organization
- Org B must never resolve Org A's provider ID or tokens

## Integration Management UI

Provider-agnostic Settings surface:

```
Settings → Integrations → Marketing
  [Meta Business] [Google Ads] [LinkedIn] [Microsoft Ads] [TikTok Ads]
```

Each card exposes the same actions:

- Status (from Provider Platform)
- Connect / Reconnect (generic OAuth entry: `marketing.providers.connect`)
- Disconnect
- View Details (never shows tokens)

Catalog-only providers (no driver yet) show **Coming soon**. Adding a driver registers Connect without UI redesign.

Permissions: `integrations.view`, `integrations.manage`.

## Service Boundaries

| Layer | Responsibility |
| --- | --- |
| `MarketingProviderService` | Single write authority: register, credentials, status, disconnect, cards |
| Controllers | Thin HTTP / redirects |
| Views | Passive-only; no token access |
| Adapters | Translate provider APIs; never persist Eloquent |

## Future Extensibility

Marketing providers (Meta, Google Ads, LinkedIn, Microsoft Ads, TikTok) plug in via:

1. Catalog entry
2. Driver class implementing `MarketingProviderInterface`
3. Optional platform `.env` app credentials

The same tenant-credential pattern applies to future modules (Gmail, Twilio, Stripe, OpenAI, …): platform app secrets in env; customer tokens in encrypted org-scoped tables. Do not invent per-vendor schema columns.

## Related

- `docs/P7_PHASE_7C1_IMPACT_REPORT.md` — Provider Platform foundation
- `docs/P7_PHASE_7C2_IMPACT_REPORT.md` — Meta OAuth foundation (uses this separation)
