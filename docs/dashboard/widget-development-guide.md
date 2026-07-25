# Widget Development Guide

## Contract

Implement `App\Contracts\DashboardWidgetDataProviderInterface` or extend `AbstractWidgetProvider`.

Required capabilities:

- Authorization (RBAC permission slug)
- Subscription validation (`subscriptionModule()`)
- Visibility rules (`isVisible()`)
- Data loading (`load()` / `fetchData()`)
- Configuration schema
- Refresh interval
- Cache key generation

## Example

```php
class MyWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string { return 'my_widget'; }
    public function subscriptionModule(): ?string { return 'crm'; }
    public function permissionSlug(): ?string { return 'leads.view'; }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        return ['count' => Lead::query()->count()];
    }
}
```

## Registration

Add widget definition to `config/dashboard.php` under `widgets`, then run:

```bash
php artisan db:seed --class=DashboardPlatformSeeder
```

Or register programmatically via `DashboardWidgetService::register()`.

## Data loading

- Eager: `DashboardService::build($user, $org, includeData: true)`
- Lazy: `GET /dashboard/widgets/{widgetKey}/data`
- Refresh: `POST /dashboard/widgets/{widget}/refresh`

## Testing

Cover permission denial, subscription filtering, and data shape in feature tests.
