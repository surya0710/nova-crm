# Stabilization Bugfix 03 – Saved Filters

## Root Cause

Saved Filters were implemented with the correct backend architecture (`filter_definition` → `MetadataQueryRequest` → `MetadataQueryService` → projections), but manual QA exposed **UI integration gaps** that made save/load unreliable in the browser.

### Primary defects

1. **Invalid nested forms** – Save, rename, duplicate, and delete actions were rendered inside the GET index filter `<form>`. HTML forbids nested forms, so POST actions were unreliable or ignored by browsers.
2. **Stale save payload** – The save form serialized last server-rendered `$filters` via hidden inputs instead of the user's current form selections. Saving without clicking **Filter** first persisted outdated criteria.
3. **Saved filter state overridden on load** – `SavedFilterService::resolveIndexInput()` used `array_replace($savedParameters, $request)`, allowing empty or conflicting query parameters (for example `status=`) to override stored saved-filter criteria.
4. **Incomplete load UX** – The load dropdown was hidden when no filters existed, there was no clear-active-filter action, and active-filter visibility metadata was not surfaced consistently.

No redesign of `SavedFilterService` or the Metadata Platform query path was required. The service layer was already correct; the defects were integration and state-restoration issues.

## Investigation Summary

| Layer | Finding |
| --- | --- |
| Save workflow | `SavedFilterController@store` → `SavedFilterService::create()` persisted correctly when valid POST data arrived |
| Load workflow | `resolveIndexInput()` + `MetadataQueryDefinitionService` rebuilt requests correctly when `saved_filter` was the sole driver |
| UI | Nested forms and stale hidden fields broke save/rename/delete in real browsers |
| State restoration | Controllers passed expanded `$filters` to views, but load merge order and form structure prevented faithful restoration |
| Validation | Obsolete metadata already degraded to `partial` / `invalid` without exceptions |
| Permissions | Organization scope existed; policy now also asserts current tenant explicitly |
| Query execution | Confirmed projection-only execution path (no regression) |

## Fix Implemented

### 1. Separate saved-filter forms from the GET filter form

Lead, Customer, and Pipeline index pages now close the GET filter form before including `_saved_filter_controls`. POST actions are no longer nested.

### 2. Live form synchronization before save/update

`_saved_filter_controls.blade.php` copies the current index filter form into save/update POST payloads on submit using `FormData`, so users can save unsubmitted form changes.

### 3. Saved filter definition is authoritative on load

`resolveIndexInput()` now returns stored query parameters when `saved_filter` is present, preserving only `page` from the request for pagination.

### 4. Load UX improvements

- Saved-filter dropdown is always visible
- **Clear saved filter** option appears when a filter is active
- Active filter panel shows visibility (private/shared)
- **Update criteria** action overwrites the active saved filter definition from current index filters

### 5. Security hardening

`SavedFilterPolicy` now verifies the saved filter belongs to the current tenant via `TenantContext`, in addition to entity permissions and ownership rules.

### 6. Invalid saved filter redirect

`AppliesSavedIndexFilters` redirects invalid/unavailable saved-filter lookups back to the entity index with validation errors.

## Files Modified

- `app/Services/SavedFilterService.php`
- `app/Policies/SavedFilterPolicy.php`
- `app/Http/Controllers/Concerns/AppliesSavedIndexFilters.php`
- `app/Http/Controllers/SavedFilterController.php`
- `app/Http/Requests/UpdateSavedFilterRequest.php`
- `resources/views/metadata-fields/_saved_filter_controls.blade.php`
- `resources/views/leads/index.blade.php`
- `resources/views/customers/index.blade.php`
- `resources/views/pipeline/index.blade.php`
- `tests/Feature/MetadataSavedFilterIntegrationTest.php`
- `docs/STABILIZATION_BUGFIX_03_SAVED_FILTERS.md`

## Architectural Impact

No changes to Metadata Platform contracts:

- `docs/METADATA_RUNTIME_CONTRACT.md`
- `docs/METADATA_VALIDATION_CONTRACT.md`
- `docs/METADATA_QUERY_CONTRACT.md`

Saved filters still execute exclusively through:

```
SavedFilter.filter_definition
  → MetadataQueryRequest
  → MetadataQueryService
  → metadata_value_projections
```

Controllers remain thin orchestration layers. `SavedFilterService` was not redesigned.

## Security Impact

| Control | Status |
| --- | --- |
| Private filter visibility | Owner only |
| Shared filter visibility | Organization members with entity view permission |
| Update/delete | Owner or `metadata.manage` on shared filters |
| Cross-tenant access | Blocked by organization scope + explicit tenant policy check |
| Invalid/deleted filters | Rejected with validation error, no query execution |

## Regression Impact

| Area | Impact |
| --- | --- |
| Lead metadata filters | No change |
| Customer metadata filters | No change |
| Opportunity metadata filters | No change |
| Search | No change |
| REST API metadata queries | No change |
| Projection synchronization | No change |
| Manual index filtering (no saved filter) | No change |

## Tests Executed

```bash
php artisan test --filter=MetadataSavedFilter
php artisan test --filter=Metadata
php artisan test
```

### New/expanded coverage in `MetadataSavedFilterIntegrationTest`

- Save: create, overwrite criteria, unsubmitted form capture
- Load: private, shared, invalid, deleted, UI state restoration
- Execution: mixed static + metadata filters, sorting, pagination
- Security: cross-tenant rejection, unauthorized update
- Metadata evolution: inactive and archived fields degrade to `partial` without exceptions

## Manual QA Completed

- Save filter from index with static + metadata criteria (without clicking Filter first)
- Load saved filter from dropdown; active filter highlighted
- Clear saved filter from dropdown
- Rename, duplicate, delete active filter
- Update criteria on active saved filter
- Partial validation banner when metadata field is archived
- Pagination retains `saved_filter` parameter

## Deployment Notes

- Forward-only deployment; no migrations required beyond existing `saved_filters` table
- No artisan rebuild commands required
- Clear browser cache not required; Blade and inline script changes apply on deploy
