<?php

namespace App\Http\Controllers\Concerns;

use App\Models\SavedFilter;
use App\Services\SavedFilterService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

trait AppliesSavedIndexFilters
{
    /**
     * @return array{input: array<string, mixed>, activeSavedFilter: ?SavedFilter, savedFilters: Collection<int, SavedFilter>}
     */
    protected function resolveSavedIndexFilters(
        Request $request,
        TenantContext $tenant,
        string $entityType,
        SavedFilterService $savedFilters,
    ): array {
        $organization = $tenant->get();

        try {
            $input = $savedFilters->resolveIndexInput(
                $request->user(),
                $organization->id,
                $entityType,
                $request->all(),
            );
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(route($this->savedFilterIndexRoute($entityType)));
        }

        $activeSavedFilter = null;

        if ($savedFilterId = (int) ($input['saved_filter'] ?? 0)) {
            $activeSavedFilter = $savedFilters->findAccessible(
                $request->user(),
                $organization->id,
                $savedFilterId,
                $entityType,
            );
        }

        return [
            'input' => $input,
            'activeSavedFilter' => $activeSavedFilter,
            'savedFilters' => $savedFilters->availableFor($request->user(), $organization->id, $entityType),
        ];
    }

    protected function savedFilterIndexRoute(string $entityType): string
    {
        return match ($entityType) {
            'lead' => 'leads.index',
            'customer' => 'customers.index',
            'opportunity' => 'pipeline.index',
            default => 'dashboard',
        };
    }
}
