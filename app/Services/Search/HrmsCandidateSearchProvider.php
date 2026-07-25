<?php

namespace App\Services\Search;

use App\Models\Candidate;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;

class HrmsCandidateSearchProvider implements SearchProviderInterface
{
    public function key(): string
    {
        return 'candidates';
    }

    public function label(): string
    {
        return __('Candidates');
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

        return Candidate::query()
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%")
                    ->orWhere('current_company', 'like', "%{$query}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$query}%"]);
            })
            ->limit($limit)
            ->get()
            ->map(fn (Candidate $candidate) => [
                'type' => __('Candidate'),
                'label' => $this->label(),
                'title' => $candidate->fullName(),
                'subtitle' => $candidate->email ?? $candidate->current_company,
                'url' => route('hrms.recruitment.candidates.show', $candidate),
                'workspace' => 'hr',
            ]);
    }
}
