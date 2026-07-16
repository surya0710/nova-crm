# NovaCRM Phase 7C.5 — What Was Done

## Phase

**Phase 7C.5 — Meta Lead Entry Import (Manual Synchronization)**

## One-sentence summary

Administrators can manually import Meta Lead Form submissions into CRM leads through `LeadService`, with idempotent duplicate prevention and import-run statistics — without webhooks, polling, or field-mapping UI.

---

## Context (what was already complete)

| Phase | Deliverable |
| --- | --- |
| Marketing Platform | Frozen SoT for tracking, attribution, conversions |
| 7C.1 | Provider Platform foundation (interface, registry, credentials, service) |
| 7C.2 | Meta OAuth connect / disconnect / health |
| 7C.3 | Meta asset discovery + selection (business, ad account, page, pixel, forms) |
| 7C.4 | Meta lead **form metadata** catalog sync |
| **7C.5** | **Manual lead entry import** ← this phase |

---

## Objective delivered

Implement administrator-triggered import of lead submissions from selected Meta Lead Forms into NovaCRM.

Explicitly **not** delivered in this phase:

- Webhook subscriptions or processing
- Automatic / background synchronization
- Scheduled polling
- Field mapping UI
- Campaign sync, offline conversions, audiences, reporting

---

## Architecture (as built)

```
Integrations UI → Import Leads
  → IntegrationController::importLeads
      → MarketingProviderService::importLeadEntries   ← single orchestration authority
          → MarketingProviderLeadImportInterface
              → MetaMarketingProvider::importLeadEntries
                  → MetaGraphClient::listFormLeads
          → Dedup check (marketing_provider_imported_leads)
          → LeadService::create                         ← normal CRM pipeline
          → marketing_provider_lead_import_runs         ← stats / history
```

Marketing Platform contracts were **not** modified. Attribution and conversion still run only through `LeadService` (no-op when there is no visitor — lead is still created).

---

## What was implemented

### 1. Graph lead-entry fetch

**File:** `MetaGraphClient`

- Added `listFormLeads($formId, $accessToken)`
- Endpoint: `GET /{form-id}/leads`
- Fields: `id`, `created_time`, `ad_id`, `ad_name`, `form_id`, `field_data`
- Uses existing pagination helper
- No CRM writes in the client

### 2. Provider adapter (DTO only)

**File:** `MetaMarketingProvider`

- Capability: `lead_import`
- Implements optional `MarketingProviderLeadImportInterface::importLeadEntries()`
- Reads selected form IDs from `credential.configuration.lead_form_ids`
- Normalizes standard Meta fields:
  - `full_name`, `first_name`, `last_name` → name
  - `email` → email
  - `phone_number` / `phone` → phone
  - `company_name` / `company` → company
- All other fields → `unmapped_fields`
- Continues on per-form / per-entry failures
- Expired / revoked tokens surface as provider status
- **No Eloquent persistence in the adapter**

Frozen `MarketingProviderInterface` was **not** changed. Discovery/sync/import use additive optional interfaces (same pattern as 7C.3 / 7C.4).

### 3. Import orchestration

**File:** `MarketingProviderService`

- Injects `LeadService`
- `importLeadEntries($provider, $user, $options = [])`:
  1. Adapter fetch
  2. Skip if `(organization, provider, external_lead_id)` already imported
  3. Map DTO → lead payload (`source = facebook`)
  4. `LeadService::create(...)` with importing admin as `created_by`
  5. Persist dedup row + import run
- `latestLeadImportRun($provider)` for UI
- `supportsLeadImport($provider)` capability check

Unmapped Meta values and provider identifiers are stored under:

```json
lead.custom_fields.provider = {
  "slug": "meta",
  "external_lead_id": "...",
  "external_form_id": "...",
  "unmapped_fields": { ... },
  "ad_id": "...",
  "ad_name": "..."
}
```

### 4. Duplicate prevention

**Table:** `marketing_provider_imported_leads`

| Column | Purpose |
| --- | --- |
| `organization_id` | Tenant scope |
| `marketing_provider_id` | Provider connection |
| `external_lead_id` | Meta leadgen ID |
| `external_form_id` | Source form |
| `lead_id` | CRM lead FK |
| `raw_payload` | Normalized/raw Graph payload |
| `imported_at` | When imported |

Unique key: `(organization_id, marketing_provider_id, external_lead_id)`

Repeat imports **skip** already-seen Meta lead IDs — never create duplicate CRM leads for the same entry.

### 5. Import history / statistics

**Table:** `marketing_provider_lead_import_runs`

Tracks per run:

- `imported_count`
- `skipped_count`
- `failed_count`
- `status` — `completed` | `partial` | `failed`
- `message`
- `metadata` (fetched counts + sampled errors)
- `triggered_by` (user)
- `imported_at`

### 6. Integration Management UI

On Meta integration details (when connected + `lead_import` supported):

- **Import Leads** button (manual only)
- Last import timestamp
- Imported / skipped / failed counts
- Route: `POST integrations/{provider}/leads/import`
- Permission: `integrations.manage`
- Tokens never shown

### 7. Error handling

| Condition | Behavior |
| --- | --- |
| Expired / revoked token | Mark provider status; failed run; no new leads |
| Deleted / inaccessible form | Skip form; continue others |
| Malformed entry | Count failed; continue |
| Partial create failures | Partial run status; errors sampled in metadata |
| No forms selected | No-op success |

### 8. Multi-tenancy

- Import scoped to the initiating organization’s provider connection
- Dedup keys and leads always carry `organization_id`
- Cross-tenant provider IDs cannot be resolved

---

## Key files

```
app/Contracts/MarketingProviderLeadImportInterface.php
app/Services/Marketing/Providers/MetaGraphClient.php          (+ listFormLeads)
app/Services/Marketing/Providers/MetaMarketingProvider.php    (+ importLeadEntries)
app/Services/MarketingProviderService.php                     (+ orchestration)
app/Models/MarketingProviderImportedLead.php
app/Models/MarketingProviderLeadImportRun.php
database/migrations/2026_07_16_000006_create_marketing_provider_imported_leads_table.php
database/migrations/2026_07_16_000007_create_marketing_provider_lead_import_runs_table.php
app/Http/Controllers/IntegrationController.php                (+ importLeads)
resources/views/integrations/show.blade.php                   (+ Import Leads UI)
routes/web.php                                                (+ integrations.leads.import)
tests/Feature/MetaLeadImportTest.php
docs/P7_PHASE_7C5_IMPACT_REPORT.md
```

---

## Import lifecycle

```
Connect Meta (7C.2)
  → Discover & select assets including lead forms (7C.3)
  → Synchronize form metadata catalog (7C.4)
  → Admin clicks Import Leads (7C.5)
      → Fetch entries for selected forms
      → For each entry:
           already imported? → skip
           else → LeadService::create → record external ID
      → Persist import run stats
```

---

## Testing (quality gate at completion)

New suite: `tests/Feature/MetaLeadImportTest.php`

Covered:

- First import creates leads through `LeadService`
- Repeat import skips duplicates
- Malformed entry partial failure
- Expired credentials
- Tenant isolation
- UI import + stats rendering
- Marketing Platform not bypassed (no visitor creation); leads still created without attribution when no visitor

| Suite | Result |
| --- | --- |
| Provider + Meta | 64 passed (307 assertions) |
| Marketing filter | 132 passed (530 assertions) |
| Full suite | **614 passed (2137 assertions)**, 0 failures |

---

## Acceptance criteria checklist

- [x] Manual Meta lead import works
- [x] Imported leads created through `LeadService`
- [x] Duplicate imports prevented via external lead IDs
- [x] Import statistics recorded and shown in UI
- [x] Marketing Platform contracts untouched
- [x] No webhooks or polling
- [x] Comprehensive tests added
- [x] Impact report completed
- [x] Full suite green with zero regressions

---

## What comes next (not started)

**Phase 7C.6 — Meta Webhook Integration** (real-time lead delivery)

Do not begin webhooks until 7C.5 is reviewed. Do not add field-mapping or automatic polling as part of 7C.5 follow-ups unless a new phase prompt opens that scope.
