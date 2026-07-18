# P7 Marketing Attribution Platform - Phase 7E.1 Impact Report

## Phase

Phase 7E.1 - Provider Diagnostics & Health Center

## Outcome

NovaCRM now exposes a unified **Provider Diagnostics & Health Center** for all
marketing integrations. The Provider Platform aggregates health, credential
state, synchronization history, runtime statistics, and recent errors into one
tenant-scoped dashboard.

Meta, Google Ads, and every future provider participate automatically through
existing adapter health checks and synchronization runtime history. No
provider-specific dashboards, contracts, or schema changes were introduced.

## Diagnostics Architecture

```text
Integration Management UI
        |
        v
IntegrationController
        |
        +--> MarketingProviderService          (unchanged write authority)
        |
        v
ProviderDiagnosticsService                   (new aggregation layer)
        |
        +--> integration catalog cards
        +--> marketing_providers / credentials
        +--> marketing_provider_sync_runs
        +--> marketing_provider_lead_import_runs
        +--> marketing_provider_imported_leads
        +--> marketing_provider_uploaded_conversions
        +--> marketing_provider_webhook_events
        |
        v
MarketingProviderInterface::reportHealth()   (per-provider adapter)
        |
        v
Unified Diagnostics Dashboard
```

Responsibilities are split as required:

| Layer | Owns |
| --- | --- |
| Provider adapters | Health information via `reportHealth()` |
| `MarketingProviderService` | Credential writes, `checkHealth()`, synchronization lifecycle |
| `ProviderDiagnosticsService` | Aggregation, normalization, presentation payloads |
| Integration UI | Unified diagnostics dashboard only |

No provider-specific logic exists in the diagnostics service.

## Aggregation Model

`ProviderDiagnosticsService::diagnosticsForOrganization()` merges:

1. The integration catalog from `MarketingProviderService::integrationCardsForOrganization()`
2. Tenant connection rows for the requested organization
3. Normalized health, credential, synchronization, statistics, and error payloads

Each provider entry contains:

- `connection` — connected / disconnected / expired / error
- `health` — canonical health state and labels
- `credentials` — OAuth state, expiry, refresh availability, revocation
- `synchronization` — last / last successful / last failed / last upload / last import / recent runs
- `statistics` — inbound, outbound, and general runtime counters
- `errors` — recent provider, synchronization, import, and webhook failures
- `highlights` — last upload, last import, last health check

Organization-scoped reads bypass the fail-open tenant global scope when an
explicit `organization_id` is supplied, so diagnostics remain accurate even when
the active tenant context differs from the organization being inspected.

## Provider Health Model

Canonical health states:

| State | Meaning |
| --- | --- |
| `healthy` | Connected and operating normally |
| `degraded` | Connected but recent partial synchronization outcomes |
| `unhealthy` | Provider status is `error` |
| `expired_credentials` | Provider or credential expiry detected |
| `revoked_credentials` | Revoked / invalid-grant signals in credential metadata or errors |
| `disconnected` | No active tenant connection |

Connection status continues to use the frozen provider vocabulary:
`connected`, `disconnected`, `expired`, `error`.

Manual health checks call the existing `MarketingProviderService::checkHealth()`
path, which invokes `MarketingProviderInterface::reportHealth()` and updates
`last_health_at`.

## Synchronization Summary

Synchronization summaries reuse immutable `marketing_provider_sync_runs` history
without schema changes.

For each provider the dashboard exposes:

| Field | Source |
| --- | --- |
| Last synchronization | Latest sync run |
| Last successful synchronization | Latest `completed` or `partial` run |
| Last failed synchronization | Latest `failed` run |
| Last upload | Latest `conversion_upload` run |
| Last import | Latest `marketing_provider_lead_import_runs` row |
| Sync type / direction / duration / processed / failed | Normalized sync-run DTO |

Recent synchronization history (last 10 runs) is included for operational
context.

## Statistics Model

Statistics are computed from existing runtime tables only. Nothing is
duplicated into a new metrics store.

| Category | Metric | Source |
| --- | --- | --- |
| Inbound | Imported leads | `marketing_provider_imported_leads` |
| Inbound | Webhook events processed | `marketing_provider_webhook_events` (`processed`) |
| Outbound | Uploaded conversions | `marketing_provider_uploaded_conversions` |
| General | Synchronization count | `marketing_provider_sync_runs` |
| General | Success count | `completed` + `partial` runs |
| General | Failure count | `failed` runs |

## Error Summary

Recent errors are assembled from existing runtime information:

- `marketing_providers.last_error`
- Failed synchronization runs (`message`)
- Failed lead import runs (`message`)
- Failed webhook events (`failure_reason`)

Results are sorted by recency and capped at 10 entries. No new logging system
was introduced.

## Security and Multi-Tenancy

- Diagnostics are rendered only for the current organization via tenant middleware.
- Every query is constrained by explicit `organization_id` and, where applicable,
  `marketing_provider_id` or provider slug.
- Access tokens and refresh tokens are never included in diagnostics payloads.
- `integrations.view` is required to view the dashboard.
- `integrations.manage` is required to run manual health checks.
- Cross-tenant provider IDs, credentials, sync history, and statistics are never
  mixed because aggregation always starts from the requested organization.

## Integration Management UI

New surfaces:

| Route | Purpose |
| --- | --- |
| `GET integrations/diagnostics` | Unified diagnostics dashboard |
| `POST integrations/{provider}/health-check` | Manual health check |

The integrations index links to **Diagnostics & Health**. The dashboard shows
every catalog provider in one place with:

- connection and health badges
- last upload / import / health check highlights
- credential status
- runtime statistics
- synchronization summary
- recent errors
- **Run Health Check** for connectable connected providers

No provider-specific pages were added.

## Automatic Provider Participation

Every registered provider participates without custom platform code because the
diagnostics layer only depends on frozen contracts and existing persistence:

1. **Catalog registration** — `config/marketing.php` catalog + registry driver
   makes the provider appear on the dashboard automatically.
2. **Health** — adapters implement `reportHealth()`; manual checks reuse
   `MarketingProviderService::checkHealth()`.
3. **Credentials** — tenant credential rows already store expiry, refresh token
   presence, and metadata.
4. **Synchronization** — any operation that uses the Synchronization Runtime
   automatically contributes history and statistics.
5. **Inbound / outbound counters** — lead imports, webhook processing, and
   conversion uploads already write to provider runtime tables consumed by
   diagnostics.

Adding a future provider requires only:

1. Catalog entry
2. Adapter implementing `MarketingProviderInterface`
3. Optional capabilities (`lead_import`, `webhooks`, `offline_conversions`, …)

No diagnostics service changes are required.

## Testing Summary

New suite:

- `tests/Feature/ProviderDiagnosticsTest.php` (12 tests)

Coverage:

- organization-level aggregation across the full catalog
- health, credential, synchronization, and statistics normalization
- success / failure synchronization summaries
- degraded health after partial runs
- expired credentials
- revoked credentials
- manual health check service + HTTP action
- unified dashboard rendering
- permission enforcement
- tenant isolation for aggregation and statistics
- Meta and Google participation without provider-specific platform code
- unhealthy provider mapping

Verification:

- Provider suites: **171 passed (910 assertions)**
- Marketing suite: **95 passed (372 assertions)**
- Full suite: **713 passed (2691 assertions)**, 0 failures
- Quality gate delta from Phase 7D.3: **+13 tests, +70 assertions**

## What Did Not Change

- frozen Marketing Platform contracts and services
- frozen Provider Platform contracts (`MarketingProviderInterface`, registry,
  synchronization interfaces)
- Synchronization Runtime code and schema
- `MarketingProviderService` orchestration semantics
- Meta provider behavior
- Google Ads provider behavior
- CRM, Revenue, and Metadata Platform

## Completion

- Unified diagnostics dashboard for all providers. Done.
- Health status normalized across providers. Done.
- Synchronization history summarized from runtime tables. Done.
- Credential state displayed. Done.
- Runtime statistics displayed without duplication. Done.
- Manual health checks work. Done.
- No provider-specific dashboard exists. Done.
- Marketing Platform remains unchanged. Done.
- Synchronization Runtime remains unchanged. Done.
- No Provider Platform contracts were modified. Done.
- Comprehensive tests added. Done.
- Full suite passes with zero regressions. Done.

Phase 7E.1 is complete. Import Platform work has not been started.
