<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\ProjectTemplate;
use App\Models\User;
use App\Services\TenantContext;

class TemplateUsageWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'template_usage';
    }

    public function subscriptionModule(): ?string
    {
        return 'projects';
    }

    public function permissionSlug(): ?string
    {
        return 'projects.templates.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $query = ProjectTemplate::query();

        $templates = (clone $query)
            ->orderByDesc('usage_count')
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'slug', 'category', 'usage_count', 'is_favorite', 'version']);

        return [
            'count' => (clone $query)->count(),
            'total_usage' => (int) (clone $query)->sum('usage_count'),
            'templates' => $templates,
        ];
    }
}
