<?php

namespace App\Services\Search;

use App\Models\JobOpening;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;

class HrmsJobOpeningSearchProvider implements SearchProviderInterface
{
    public function key(): string
    {
        return 'job_openings';
    }

    public function label(): string
    {
        return __('Job Openings');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        if (! $user->hasPermission('recruitment.view')) {
            return collect();
        }

        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        return JobOpening::query()
            ->with('department')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('location', 'like', "%{$query}%")
                    ->orWhere('status', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get()
            ->map(fn (JobOpening $opening) => [
                'type' => __('Job Opening'),
                'label' => $this->label(),
                'title' => $opening->title,
                'subtitle' => trim(($opening->department?->name ?? '').' · '.($opening->status ?? '')),
                'url' => route('hrms.recruitment.openings.show', $opening),
                'workspace' => 'hr',
            ]);
    }
}
