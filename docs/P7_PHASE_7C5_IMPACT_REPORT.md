# P7 Marketing Attribution Platform - Phase 7C.5 Impact Report

## Phase

Phase 7C.5 - Meta Lead Entry Import (Manual Synchronization)

## What Changed?

Implemented **administrator-triggered** import of Meta Lead Form submissions into CRM leads. No webhooks, no polling, no scheduled sync, no field-mapping UI.

### Graph fetch

- `MetaGraphClient::listFormLeads($formId, $accessToken)` → `GET /{form-id}/leads` with `id,created_time,ad_id,ad_name,form_id,field_data`
- Paginated via existing client helper

### MetaMarketingProvider

- Capability: `lead_import`
- Implements optional `MarketingProviderLeadImportInterface::importLeadEntries()`
- Reads selected form IDs from `credential.configuration.lead_form_ids`
- Normalizes standard fields (`full_name`, `first_name`, `last_name`, `email`, `phone_number`, `phone`, `company_name`, `company`)
- Places all other fields in `unmapped_fields`
- Continues across forms/entries on partial failures; expired/revoked tokens surface as provider status
- **No Eloquent writes**

### Contract approach

- **Did not modify** frozen `MarketingProviderInterface` or Marketing Platform contracts
- Additive optional contract: `App\Contracts\MarketingProviderLeadImportInterface`
- Service resolves via `instanceof`

### MarketingProviderService orchestration

- Injects `LeadService`
- `importLeadEntries($provider, $user, $options = [])`:
  1. Adapter fetch
  2. Skip if `(organization, provider, external_lead_id)` already imported
  3. Map DTO → lead payload (`source=facebook`)
  4. `LeadService::create(...)` — attribution/conversion/audit pipeline unchanged
  5. Persist dedup row + import run stats
- `latestLeadImportRun($provider)` for UI
- `supportsLeadImport($provider)`

Unmapped Meta values and provider identifiers are stored under `lead.custom_fields.provider` (not a parallel CRM schema).

### Duplicate prevention

Table `marketing_provider_imported_leads`:

| Column | Purpose |
| --- | --- |
| `external_lead_id` | Meta leadgen ID |
| `external_form_id` | Source form |
| `lead_id` | CRM lead FK |
| unique `(organization_id, marketing_provider_id, external_lead_id)` | Idempotency |

Repeat imports skip already-seen external IDs — never create duplicate CRM leads for the same Meta entry.

### Import history

Table `marketing_provider_lead_import_runs`:

- `imported_count`, `skipped_count`, `failed_count`
- `status` (`completed` | `partial` | `failed`)
- `message`, `metadata` (includes truncated error samples)
- `triggered_by`, `imported_at`

### Integration UI

On Meta integration details:

- **Import Leads** button (manual only)
- Last import timestamp + imported / skipped / failed counts
- Route: `POST integrations/{provider}/leads/import`

### LeadService integration

Imports use `LeadService::create()` so existing pipeline behavior applies:

- Attribution (no-op without visitor — lead still created)
- Conversion recording (no-op without attribution)
- `created_by` = importing administrator
- Organization scoping via explicit `organization_id`

### What did not change

- Marketing Platform contracts / runtime services
- Webhooks / automatic sync / polling
- Field mapping UI
- Campaign sync, offline conversions, audiences, reporting

## Architecture

```
Integrations UI → Import Leads
  → IntegrationController::importLeads
      → MarketingProviderService::importLeadEntries
          → MarketingProviderLeadImportInterface
              → MetaMarketingProvider
                  → MetaGraphClient::listFormLeads
          → dedupe (marketing_provider_imported_leads)
          → LeadService::create
          → marketing_provider_lead_import_runs
```

## Import Lifecycle

```
Select forms (7C.3) → Sync form metadata (7C.4)
  → Admin clicks Import Leads
  → Fetch entries for selected forms
  → For each entry:
       already imported? → skip
       else → LeadService::create → record external ID
  → Persist import run stats
```

## Error Handling

| Condition | Behavior |
| --- | --- |
| Expired / revoked token | Mark provider status; failed run; no new leads |
| Deleted / inaccessible form | Skip form; continue others |
| Malformed entry | Count failed; continue |
| Partial create failures | Partial run status; errors sampled in metadata |
| No forms selected | No-op success |

## Multi-Tenant Behavior

Import is scoped to the initiating organization’s provider connection. Dedup keys and leads always carry `organization_id`. Cross-tenant provider IDs cannot be resolved.

## Testing Summary

New suite: `tests/Feature/MetaLeadImportTest.php`

Coverage:

- First import via LeadService
- Repeat import duplicate skip
- Malformed entry partial failure
- Expired credentials
- Tenant isolation
- UI import + stats
- Marketing Platform not written (no visitors); leads created without attribution/conversion when no visitor

Regression suites (all green):

| Suite | Result |
| --- | --- |
| Provider + Meta | **64 passed (307 assertions)** |
| Marketing filter | **132 passed (530 assertions)** |
| Full suite | **614 passed (2137 assertions)**, 0 failures |

## CTO Recommendation

Manual Meta lead import is complete. Proceed to Phase 7C.6 (Meta Webhook Integration) only after review. Do not add polling or field-mapping in this phase.
