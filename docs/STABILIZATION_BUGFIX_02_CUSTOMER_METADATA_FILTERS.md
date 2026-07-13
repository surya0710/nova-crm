# Stabilization Bugfix 02 – Customer Metadata Filters

## Root Cause

Customer metadata index filtering compiles exclusively against `metadata_value_projections` through `MetadataQueryService`. That is the correct Metadata Platform read path.

The bug had **two independent failure points**:

### 1. Customer index never applied metadata filters

`CustomerController@index` only applied static filters (`search`, `status`, `industry`, `assigned_to`) and did not:

- resolve `metadata_filters` / `metadata_sort` input
- build a `MetadataQueryRequest`
- delegate to `MetadataQueryService`

The Customer index view already rendered metadata controls (via `_index_query_controls`), but submitted metadata query parameters were ignored. Static filters continued to work because they query native customer columns directly.

### 2. Projection synchronization gap on customer metadata writes

Historical customer metadata writes used `CustomerController::storeMetadataValues()`, which called `MetadataValueStorageService::mergeValues()` directly.

That storage-only path persisted values to `customers.custom_fields` but **never invoked `MetadataProjectionService::sync()`**.

When metadata index filtering was introduced for other entities, filters compiled correctly against projections — but many existing customers had canonical JSON values with **no matching projection rows**. `whereExists` projection constraints therefore returned no matches for those customers.

Lead conversion metadata copy already routes through `MetadataEntityFormService::persistValues()` (which syncs projections), but customers created or edited through the web form before this fix did not.

## Investigation Summary

End-to-end tracing confirmed:

| Layer | Result |
| --- | --- |
| UI (`_index_query_controls`) | Submits structured `metadata_filters[{index}][key\|operator\|value]` correctly |
| Route / Controller | **Was missing** metadata query delegation; static filters worked |
| `MetadataQueryDefinitionService` | Correctly parses filterable active definitions for `customer` + `web_index` once wired |
| `MetadataQueryService` | Compiles projection-backed `whereExists` constraints; no JSON queries |
| Projection layer | Missing/stale rows for legacy customer metadata were the write-path failure point |
| SQL | Verified projection joins include `organization_id`, `entity_type = customer`, `field_key`, typed value columns, and `value_hash = 'scalar'` for scalar filters |

## Fix Implemented

### 1. Wire Customer index into the Metadata Query Platform

`CustomerController@index` now:

- resolves saved-filter input through `AppliesSavedIndexFilters`
- builds `MetadataQueryRequest` via `MetadataQueryDefinitionService::requestForWebIndex()`
- applies metadata filters and sort via `MetadataQueryService::applyForWebIndex()`
- passes `metadataFilterFields`, `metadataSortFields`, and saved-filter context to the view

Controllers remain orchestration-only. No SQL or projection logic was added to the controller.

### 2. Preserve projection sync on customer metadata writes

Customer create/update now persist metadata through `MetadataEntityFormService::persistValidatedValues()`, which synchronizes projections after storage changes. This prevents new drift.

### 3. Backfill legacy customer projections

Added forward migration:

- `database/migrations/2026_07_10_000003_backfill_customer_metadata_projections.php`

This rebuilds customer projection rows for every organization from canonical `custom_fields` JSON.

Manual rebuild remains available when needed:

```bash
php artisan metadata:projections:rebuild --organization_id={id} --entity_type=customer
```

## Files Changed

- `app/Http/Controllers/CustomerController.php` — metadata query integration; write path via `MetadataEntityFormService`
- `database/migrations/2026_07_10_000003_backfill_customer_metadata_projections.php` — legacy projection backfill
- `tests/Feature/CustomerMetadataFilterBugfixTest.php` — regression coverage

## Architectural Impact

- Customer index now consumes the existing Metadata Query Platform identically to Lead and Opportunity indexes.
- Projection remains the only metadata query source.
- No Metadata contracts (`METADATA_RUNTIME_CONTRACT.md`, `METADATA_VALIDATION_CONTRACT.md`, `METADATA_QUERY_CONTRACT.md`) were modified.
- Controllers remain thin.

## Regression Impact

| Area | Impact |
| --- | --- |
| Customer metadata filters | Fixed — index applies metadata filters; projections backfilled |
| Customer static filters | No behavioral change |
| Lead filters | No behavioral change |
| Opportunity filters | No behavioral change |
| Saved filters | No behavioral change (customer saved filters now execute correctly) |
| Global search | No behavioral change |
| REST APIs | No behavioral change (`Api\CustomerController` already used metadata queries) |
| Metadata contracts | Unchanged |

## Tests Added

`tests/Feature/CustomerMetadataFilterBugfixTest.php` covers:

- single metadata filter
- multiple metadata filters
- metadata + static filter composition
- invalid metadata field rejection
- inactive metadata field ignore behavior
- tenant isolation
- pagination + metadata sort compatibility
- legacy storage backfill via projection rebuild
- web form create path with automatic projection sync
- lead conversion metadata copy with filterable customer fields

## Verification Results

Executed:

```bash
php artisan test --filter=Customer
php artisan test --filter=Metadata
php artisan test
```

Results:

- `php artisan test --filter=Customer` → **31 passed**
- `php artisan test --filter=Metadata` → **131 passed**
- `php artisan test` → **408 passed (zero failures)**

## Deployment Notes

1. Run forward migrations (`php artisan migrate`) so customer projections are backfilled from existing `custom_fields` JSON.
2. No contract changes are required.
3. If a tenant still reports stale metadata filter results after deploy, run:

   ```bash
   php artisan metadata:projections:rebuild --organization_id={id} --entity_type=customer
   ```
