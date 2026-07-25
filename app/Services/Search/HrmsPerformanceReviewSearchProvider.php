<?php

namespace App\Services\Search;

use App\Models\Organization;
use App\Models\PerformanceReview;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class HrmsPerformanceReviewSearchProvider implements SearchProviderInterface
{
    public function key(): string
    {
        return 'performance_reviews';
    }

    public function label(): string
    {
        return __('Performance Reviews');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        if (! $user->hasAnyPermission(['performance.view', 'performance.review.view'])) {
            return collect();
        }

        if (! Schema::hasTable('performance_reviews')) {
            return collect();
        }

        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        return PerformanceReview::query()
            ->with(['employee', 'assignment'])
            ->where(function ($q) use ($query) {
                $q->where('status', 'like', "%{$query}%")
                    ->orWhereHas('employee', function ($employee) use ($query) {
                        $employee->where('first_name', 'like', "%{$query}%")
                            ->orWhere('last_name', 'like', "%{$query}%")
                            ->orWhere('employee_code', 'like', "%{$query}%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$query}%"]);
                    });
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (PerformanceReview $review) => [
                'type' => __('Performance Review'),
                'label' => $this->label(),
                'title' => $review->employee?->full_name ?? __('Review'),
                'subtitle' => $review->status,
                'url' => route('hrms.performance.reviews.show', $review),
                'workspace' => 'hr',
            ]);
    }
}
