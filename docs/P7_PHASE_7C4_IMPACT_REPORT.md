# P7 Marketing Attribution Platform - Phase 7C.4 Impact Report

## Phase

Phase 7C.4 - Meta Lead Form Synchronization

## What Changed?

Implemented **local catalog synchronization of Meta Lead Form metadata** for forms the organization has explicitly selected. No lead submissions are imported. No webhooks are registered. Marketing Platform remains untouched.

### Local catalog entity

- Additive migration: `marketing_provider_lead_forms`
- Model: `App\Models\MarketingProviderLeadForm` (`BelongsToOrganization`)
- Fields: `external_form_id`, `external_page_id`, `name`, `status` (`active`|`inactive`), `locale`, `questions` (JSON), `raw_metadata` (JSON), `last_synced_at`
- Unique: `(organization_id, marketing_provider_id, external_form_id)`
- Factory: `MarketingProviderLeadFormFactory`
- Relation: `MarketingProvider::leadForms()`

Historical rows are never hard-deleted. Removed / deselected / deleted-on-Meta forms are marked `inactive`.

### Meta Graph client

- `MetaGraphClient::getLeadForm($formId, $accessToken)`  
  Fields: `id,name,status,locale,questions,updated_time`

### MetaMarketingProvider

- Capability: `lead_form_sync` (alongside `oauth`, `asset_discovery`)
- Implements optional `MarketingProviderLeadFormSyncInterface::synchronizeLeadForms()`
- Iterates `credential.configuration.lead_form_ids`
- Normalizes questions to `{id,key,label,type,options}`
- Continues on per-form failures; expired/revoked tokens fail remaining forms
- **No Eloquent writes**

### Contract approach

- **Did not modify** frozen `MarketingProviderInterface` or Marketing Platform contracts
- Additive optional contract: `App\Contracts\MarketingProviderLeadFormSyncInterface`
- Service resolves via `instanceof` — no Meta branches in registry/service

### MarketingProviderService (single write authority)

- `synchronizeLeadForms($provider)` — adapter fetch → upsert catalog → mark inactive
- `listLeadForms($provider, $activeOnly = false)`
- `supportsLeadFormSync($provider)`
- Updates `marketing_providers.last_synced_at`
- On expired/error status from adapter: updates provider status **without** wiping catalog rows

### Synchronization strategy

| Scenario | Behavior |
| --- | --- |
| First sync | Create catalog rows for selected forms |
| Incremental sync | Upsert by external form id (idempotent) |
| Metadata change | Update name, locale, questions, raw_metadata |
| Form deleted on Meta | Mark `inactive`; retain prior metadata |
| Form deselected in configuration | Mark `inactive` on next sync |
| Partial failure | Sync remaining forms; report synced/failed counts |
| No forms selected | No-op success |

### Integration Management UI

On Meta integration details (when connected + `lead_form_sync`):

- **Synchronize Forms** action → `POST integrations/{provider}/lead-forms/sync`
- Catalog table: Form Name, Status, Locale, Question count, Last Synced
- Does not render raw JSON or tokens

### What did not change

- Marketing Platform runtime / contracts
- Lead Ads entry import
- Webhook subscription or processing
- Field mapping to CRM leads
- Campaign sync, offline conversions, audiences

## Architecture

```
Integrations UI
  → IntegrationController::synchronizeLeadForms
      → MarketingProviderService::synchronizeLeadForms   ← single write authority
          → MarketingProviderLeadFormSyncInterface
              → MetaMarketingProvider
                  → MetaGraphClient::getLeadForm
          → marketing_provider_lead_forms (upsert / inactive)
```

## Storage Model

```json
questions: [
  {"id": "...", "key": "email", "label": "Email", "type": "EMAIL", "options": null}
]

raw_metadata: {
  "provider_status": "ACTIVE",
  "updated_time": "...",
  "question_count": 3
}
```

Catalog `status` is NovaCRM lifecycle (`active` / `inactive`). Meta’s form status is preserved in `raw_metadata.provider_status`.

## Security & Multi-Tenancy

- Sync scoped to the tenant’s provider connection and selected form IDs
- Catalog rows always carry `organization_id`
- Cross-tenant provider IDs cannot be resolved
- Tokens never exposed in UI
- Expired credentials mark provider expired and create no new catalog rows

## Testing Summary

New suite: `tests/Feature/MetaLeadFormSyncTest.php`

Coverage:

- First sync + question JSON persistence
- Incremental idempotent metadata updates
- Deleted → inactive (not hard-deleted)
- Deselected → inactive
- Expired credentials
- Tenant isolation
- UI synchronize + listing (no tokens / no raw JSON)
- Marketing Platform / CRM leads untouched

Regression suites (all green):

| Suite | Result |
| --- | --- |
| Provider + Meta | Passed |
| Marketing filter | **131 passed (527 assertions)** |
| Full suite | **606 passed (2085 assertions)**, 0 failures |

## CTO Recommendation

Meta Lead Form metadata synchronization is complete. Proceed to Phase 7C.5 (Meta Lead Entry Import) only after review. Do not register webhooks or create CRM leads in this phase.
