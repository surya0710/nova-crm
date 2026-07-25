<?php

namespace App\Services\Search;

use App\Models\EmployeeDocument;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;

class HrmsDocumentSearchProvider implements SearchProviderInterface
{
    public function key(): string
    {
        return 'hr_documents';
    }

    public function label(): string
    {
        return __('Documents');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        if (! $user->hasPermission('hrms.view')) {
            return collect();
        }

        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        return EmployeeDocument::query()
            ->with('employee')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('category', 'like', "%{$query}%")
                    ->orWhere('verification_status', 'like', "%{$query}%")
                    ->orWhereHas('employee', function ($employee) use ($query) {
                        $employee->where('first_name', 'like', "%{$query}%")
                            ->orWhere('last_name', 'like', "%{$query}%")
                            ->orWhere('employee_code', 'like', "%{$query}%");
                    });
            })
            ->limit($limit)
            ->get()
            ->map(fn (EmployeeDocument $document) => [
                'type' => __('Document'),
                'label' => $this->label(),
                'title' => $document->title ?? $document->category ?? __('Document'),
                'subtitle' => $document->employee?->full_name,
                'url' => $document->employee
                    ? route('hrms.employees.documents.index', $document->employee)
                    : route('hrms.employees.index'),
                'workspace' => 'hr',
            ]);
    }
}
