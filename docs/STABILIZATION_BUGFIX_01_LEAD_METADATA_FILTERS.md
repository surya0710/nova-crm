# Stabilization Bugfix 01 – Lead Metadata Filters

## Root Cause

Lead metadata index filtering compiles exclusively against `metadata_value_projections` through `MetadataQueryService`. That is the correct Metadata Platform read path.

The bug was a **projection synchronization gap** on the lead metadata write path:

1. Historical lead metadata writes used `LeadController::storeMetadataValues()`, which called `MetadataValueStorageService::mergeValues()` directly.
2. That storage-only path persisted values to `leads.custom_fields` but **never invoked `MetadataProjectionService::sync()`**.
3. When metadata index filtering was introduced, filters were compiled correctly, but many existing leads had canonical JSON values with **no matching projection rows**.
4. `whereExists` projection constraints therefore returned no matches for those leads, making metadata filters appear broken or ineffective.

Static lead filters were unaffected because they query native lead columns directly.

## Investigation Summary

End-to-end tracing confirmed:

| Layer | Result |
| --- | --- |
| UI (`_index_query_controls`) | Submits structured `metadata_filters[{index}][key|operator|value]` correctly |
| Route / Controller | `LeadController@index` resolves input, builds `MetadataQueryRequest`, delegates to `MetadataQueryService` |
| `MetadataQueryDefinitionService` | Parses and validates filterable active definitions for `web_index` context |
| `MetadataQueryService` | Compiles projection-backed `whereExists` constraints; no JSON queries |
| Projection layer | Missing/stale rows for legacy lead metadata were the failure point |
| SQL | Verified projection joins include `organization_id`, `entity_type`, `field_key`, and typed value columns |

## Fix Implemented

### 1. Preserve projection sync on lead metadata writes

The lead controller now persists metadata through `MetadataEntityFormService::persistValidatedValues()`, which synchronizes projections after storage changes. This prevents new drift.

### 2. Backfill legacy lead projections

Added forward migration:

- `database/migrations/2026_07_10_000002_backfill_lead_metadata_projections.php`

This rebuilds lead projection rows for every organization from canonical `custom_fields` JSON.

Manual rebuild remains available when needed:

```bash
php artisan metadata:projections:rebuild --organization_id={id} --entity_type=lead
```

### 3. Rebuild projections when query capabilities become available

`MetadataFieldDefinitionService` now triggers `MetadataProjectionService::rebuildForField()` when:

- a definition is activated
- an active definition newly enables `is_filterable`, `is_sortable`, or `is_searchable`

This ensures existing entity JSON is projected when fields become queryable.

### 4. Scalar filter hardening

`MetadataQueryService` now scopes non-multi-select scalar filters to `value_hash = 'scalar'`, matching the sort join contract and preventing accidental matches against non-scalar projection rows.

### 5. Controller regression fix

Restored missing `DuplicateCustomerException` import in `LeadController` (removed during saved-filter integration).

## Files Modified

- `app/Http/Controllers/LeadController.php`
- `app/Services/MetadataFieldDefinitionService.php`
- `app/Services/MetadataQueryService.php`
- `database/migrations/2026_07_10_000002_backfill_lead_metadata_projections.php`
- `tests/Feature/LeadMetadataFilterBugfixTest.php`

## Regression Impact

| Area | Impact |
| --- | --- |
| Lead metadata filters | Fixed – projection-backed filtering restored |
| Customer filters | No behavioral change |
| Opportunity filters | No behavioral change |
| Global search | No behavioral change |
| Saved filters | No behavioral change |
| REST APIs | No behavioral change |
| Metadata contracts | Unchanged |

Controllers remain thin. No JSON metadata querying was introduced. Projection remains the only metadata query source.

## Tests Added / Updated

`tests/Feature/LeadMetadataFilterBugfixTest.php` covers:

- single metadata filter
- multiple metadata filters
- metadata + static filter composition
- invalid metadata field rejection
- inactive metadata field ignore behavior
- tenant isolation
- pagination + metadata sort compatibility
- legacy storage backfill via projection rebuild
- web form create path with automatic projection sync

## Verification Results

Executed:

```bash
php artisan test --filter=Lead
php artisan test --filter=Metadata
php artisan test
```

Results:

- `php artisan test --filter=Lead` → **71 passed**
- `php artisan test --filter=Metadata` → **122 passed**
- `php artisan test` → **full suite passed (zero failures)**

## Deployment Notes

1. Run forward migrations (`php artisan migrate`) so lead projections are backfilled.
2. No contract changes are required.
3. If a tenant still reports stale metadata filter results after deploy, run the rebuild command above for that organization.
