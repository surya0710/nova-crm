<?php

namespace App\Services\Search;

use App\Models\Organization;
use App\Models\User;
use App\Services\Configuration\ConfigurationRegistry;
use Illuminate\Support\Collection;

class AdminSettingsSearchProvider implements SearchProviderInterface
{
    public function __construct(
        protected ConfigurationRegistry $registry,
    ) {}

    public function key(): string
    {
        return 'settings';
    }

    public function label(): string
    {
        return __('Settings');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        $sections = $this->registry->visibleSectionsForSearch($user, $organization);
        if ($sections === []) {
            return collect();
        }

        return collect($this->registry->filterSectionsByQuery($sections, $query))
            ->take($limit)
            ->map(function (array $section) {
                $subtitle = collect([
                    $section['module_name'] ?? null,
                    $section['description'] ?? null,
                ])->filter()->implode(' · ');

                return [
                    'type' => __('Setting'),
                    'label' => $this->label(),
                    'title' => __($section['label']),
                    'subtitle' => $subtitle !== '' ? __($subtitle) : null,
                    'url' => $section['href'],
                    'workspace' => 'administration',
                ];
            })
            ->values();
    }
}
