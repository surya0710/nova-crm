<?php

namespace App\Services\Search;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class AdminTemplateSearchProvider implements SearchProviderInterface
{
    public function key(): string
    {
        return 'templates';
    }

    public function label(): string
    {
        return __('Templates');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        if (! $user->hasPermission('rbac.view') || ! Route::has('rbac.templates.index')) {
            return collect();
        }

        $query = trim(mb_strtolower($query));
        if ($query === '') {
            return collect();
        }

        $templates = [
            [
                'title' => __('Permission Templates'),
                'subtitle' => __('Install or reset role permission templates'),
                'url' => route('rbac.templates.index'),
                'keywords' => ['permission', 'template', 'rbac', 'role'],
            ],
        ];

        if (Route::has('hrms.recruitment.communication-templates.index')) {
            $templates[] = [
                'title' => __('Communication Templates'),
                'subtitle' => __('Recruitment messaging templates'),
                'url' => route('hrms.recruitment.communication-templates.index'),
                'keywords' => ['communication', 'email', 'template', 'recruitment'],
            ];
        }

        return collect($templates)
            ->filter(function (array $item) use ($query) {
                $haystack = mb_strtolower($item['title'].' '.($item['subtitle'] ?? '').' '.implode(' ', $item['keywords'] ?? []));

                return str_contains($haystack, $query);
            })
            ->take($limit)
            ->map(fn (array $item) => [
                'type' => __('Template'),
                'label' => $this->label(),
                'title' => $item['title'],
                'subtitle' => $item['subtitle'] ?? null,
                'url' => $item['url'],
                'workspace' => 'administration',
            ])
            ->values();
    }
}
