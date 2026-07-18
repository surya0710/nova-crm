# P7 Marketing Attribution Platform - Phase 7C.6 Impact Report

## Phase

Phase 7C.6 - Meta Webhook Foundation

## What Changed?

Implemented **secure Meta webhook verification and raw event recording**. No lead event processing. No CRM lead creation. No Marketing Platform writes.

### Webhook verification

- `GET /webhooks/marketing/{provider}` handles Meta subscription challenges
- Supports `hub.mode`, `hub.verify_token`, `hub.challenge`
- Returns the challenge as plain text only after successful verification
- Application-level verify token: `META_WEBHOOK_VERIFY_TOKEN` via `config('marketing.providers.meta.webhook_verify_token')`
- Not a tenant credential

### Signature validation

- `POST /webhooks/marketing/{provider}` requires `X-Hub-Signature-256`
- HMAC-SHA256 over the raw body using `META_APP_SECRET`
- Rejects missing signatures (401), invalid signatures (401), and malformed payloads (400)
- Invalid deliveries are **not** persisted (no payload logging on auth failure)

### Webhook event store

Additive table `marketing_provider_webhook_events`:

| Column | Purpose |
| --- | --- |
| `organization_id` | Nullable — org resolution deferred to next phase |
| `provider` | Adapter slug (`meta`) |
| `event_type` | Normalized type (`leadgen`, `verification`, …) |
| `delivery_id` | SHA-256 of raw body (idempotency key) |
| `payload` | JSON (`raw` + `normalized` for accepted deliveries) |
| `signature` | Received hub signature |
| `received_at` | Ingest timestamp |
| `processed_at` | Always null for business events in this phase |
| `processing_status` | `received` \| `verified` \| `duplicate` \| `rejected` |

Unique `(provider, delivery_id)` prevents duplicate rows for identical deliveries.

### Contract approach

- **Did not modify** frozen `MarketingProviderInterface` or Marketing Platform contracts
- Additive optional contract: `App\Contracts\MarketingProviderWebhookInterface`
  - `verifyWebhook(array $query)`
  - `validateAndNormalizeWebhook(string $rawBody, array $payload, array $headers)`
- Service resolves via `instanceof`

### MetaMarketingProvider

- Capability: `webhooks` (alongside oauth / asset_discovery / lead_form_sync / lead_import)
- Implements `verifyWebhook()` and `validateAndNormalizeWebhook()`
- `receiveWebhook()` delegates to signature validation + normalization
- Normalizes `leadgen` changes into DTO shape for future processing
- **No Eloquent writes**

### MarketingProviderService (single write authority)

- `verifyWebhook($slug, $query)` — adapter verify + persist verification event
- `ingestWebhook($slug, $rawBody, $headers)` — public ingest path; `organization_id = null`
- `receiveWebhook($provider, …)` — adapter validate + persist (org from provider when present)
- `webhookStatus($slug)` — last received / last verified for UI
- `supportsWebhooks($provider)`
- Exposes `processing_status`; does **not** process events

### HTTP surface

- Controller: `MetaWebhookController`
- Route: `GET|POST /webhooks/marketing/{provider}` (`webhooks.marketing`)
- CSRF-excepted (`webhooks/marketing/*`)
- Throttled (`marketing-webhooks`, default 120/min/IP)
- Unauthenticated (signature / verify-token protected)

### Integration UI

On Meta integration details (when connected + `webhooks`):

- Webhook Status (`Awaiting traffic` / `Verified` / `Receiving`)
- Last Received
- Last Verification

No event processing UI. Tokens never shown.

### What did not change

- Marketing Platform contracts / runtime services / tables
- Lead creation / import / field mapping
- Event processing / queues / retries / background jobs
- Offline conversions / campaign sync / reporting
- Organization resolution for webhook deliveries

## Architecture

```
Meta
  → GET/POST /webhooks/marketing/meta
      → MetaWebhookController
          → MarketingProviderService   ← single write authority
              → MarketingProviderWebhookInterface
                  → MetaMarketingProvider (verify / validate / normalize)
              → marketing_provider_webhook_events
```

## Verification Lifecycle

```
Meta App Dashboard → Subscribe callback URL
  → GET ?hub.mode=subscribe&hub.verify_token=…&hub.challenge=…
  → verify token against META_WEBHOOK_VERIFY_TOKEN
  → on success: persist verification event; return challenge (200 text/plain)
  → on failure: 403; no persistence
```

## Signature Validation

```
POST raw body + X-Hub-Signature-256: sha256=<hmac>
  → expected = sha256=HMAC_SHA256(raw_body, META_APP_SECRET)
  → hash_equals → accept
  → else 401; do not store payload
```

## Event Storage

Accepted deliveries are stored as raw foundation records:

- `processing_status = received`
- `processed_at = null`
- Duplicate identical bodies reuse the existing row (`duplicate: true` in service response)
- CRM leads / imported-lead rows / Marketing Platform tables are untouched

## Security Model

| Concern | Control |
| --- | --- |
| Subscription proof | Shared `META_WEBHOOK_VERIFY_TOKEN` (env / platform config) |
| Delivery authenticity | `X-Hub-Signature-256` with app secret |
| CSRF | Exempt + signature required |
| Abuse | Per-IP throttle |
| Secrets in UI | Never rendered |
| Tenant isolation | Org resolution deferred; events stored with `organization_id = null` |
| Failed auth | Reject without persisting payload |

## Multi-Tenancy

This phase does **not** resolve organization from page/form IDs. Events may land with `organization_id = null`. Tenant routing belongs to the next phase (event processing).

## Testing Summary

New suite: `tests/Feature/MetaWebhookTest.php`

Coverage:

- Verification success / failure (token, mode)
- Signature validation (missing, invalid)
- Malformed JSON / malformed Meta object
- Event persistence without CRM / Marketing Platform side effects
- Duplicate delivery idempotency
- Normalization of leadgen entries
- Integration UI webhook status
- Unknown provider 404

Regression suites (all green):

| Suite | Result |
| --- | --- |
| Provider + Meta (+ webhook) | Passed |
| Marketing filter | **132 passed (532 assertions)** |
| Full suite | **628 passed (2194 assertions)**, 0 failures |

Quality gate delta vs Phase 7C.5: +14 tests, +57 assertions.

## CTO Recommendation

Meta Webhook Foundation is complete. Proceed to Phase 7C.7 (Webhook Event Processing / organization resolution → lead intake) only after review. Do **not** begin lead creation, queues, or Marketing Platform writes from webhooks in this phase.
