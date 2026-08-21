# Configuration Registry

The Configuration Hub is a **catalog and navigation layer**. It does not store settings.

| Piece | Role |
|-------|------|
| `config/organization_settings.php` | Module and section catalog |
| `App\Services\Configuration\ConfigurationRegistry` | Plan, enabled-module, and permission filter |
| Existing routes / controllers / organization settings JSON | Source of truth |

Do not add a second settings system per product module.

## Visibility rules

A module or section is shown only when **all** of these pass:

1. The named route exists.
2. The license module is allowed by plan **and** enabled for the organization (`null` license = always available).
3. The current user has the section permission (or `fallback_permission` / `any_permissions`).

Disabled and unlicensed modules never appear in the hub, global settings search, or recently used settings.

Direct URLs stay protected by existing policies **and** `EnsureOrganizationHasModule`, which also blocks uniquely catalogued licensed routes (for example HR working days on a starter plan).

## Adding a future module

1. Ship the operational screens, policies, and storage as usual.
2. Add a module entry to `config/organization_settings.php`:

```php
'assets' => [
    'key' => 'assets',
    'name' => 'Assets',
    'description' => 'Asset policies, categories, and assignment defaults.',
    'icon' => 'box',
    'license' => 'hrms', // config/modules.php key, or null
    'permission' => null, // optional module-level gate
    'order' => 90,
    'sections' => [
        'policies' => [
            'label' => 'Asset Policies',
            'description' => 'Assignment and return rules.',
            'keywords' => ['inventory', 'custody'],
            'route' => 'hrms.assets.policies.edit', // existing route
            'permission' => 'assets.manage',
            'fallback_permission' => 'hrms.view',
            'license' => 'hrms', // optional section override
            'order' => 10,
        ],
    ],
],
```

3. Keep the module in `future_modules` until it is production-ready; then move it into `modules` and remove the future stub.
4. Do not duplicate the page under a new controller. Deep-link the existing route.
5. Settings search, hub cards, breadcrumbs, and recently used settings pick the section up automatically.

## Recently used settings

Visits to catalogued GET routes are stored on `user_ui_preferences.meta.recent_settings`. The hub only renders entries that are still visible for the current user and organization.

## Search

`AdminSettingsSearchProvider` searches visible section labels, descriptions, module names, keys, and keywords. Users who can see a section can find it — there is no extra `settings.manage` gate on search.
