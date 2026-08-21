<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSavedFilterRequest;
use App\Http\Requests\UpdateSavedFilterRequest;
use App\Models\SavedFilter;
use App\Services\SavedFilterService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class SavedFilterController extends Controller
{
    public function __construct(protected SavedFilterService $savedFilters) {}

    public function store(StoreSavedFilterRequest $request, TenantContext $tenant): RedirectResponse
    {
        $organization = $tenant->get();

        try {
            $filter = $this->savedFilters->create(
                $organization->id,
                $request->user(),
                $request->string('entity_type')->toString(),
                [
                    'name' => $request->string('name')->toString(),
                    'description' => $request->input('description'),
                    'visibility' => $request->string('visibility')->toString(),
                    'filter_definition' => $request->filterDefinition(),
                ],
            );
        } catch (ValidationException $exception) {
            return redirect()
                ->route($request->string('redirect_route')->toString(), $request->except(['_token', 'name', 'description', 'visibility', 'redirect_route', 'entity_type']))
                ->withErrors($exception->errors());
        }

        return redirect()
            ->route($request->string('redirect_route')->toString(), [
                ...$this->savedFilters->queryParameters($filter),
            ])
            ->with('status', 'saved-filter-created');
    }

    public function update(UpdateSavedFilterRequest $request, SavedFilter $savedFilter): RedirectResponse
    {
        $this->authorize('update', $savedFilter);

        $payload = [
            'name' => $request->string('name')->toString(),
            'description' => $request->input('description'),
            'visibility' => $request->string('visibility')->toString(),
        ];

        if ($definition = $request->filterDefinition()) {
            $payload['filter_definition'] = $definition;
        }

        $filter = $this->savedFilters->update($savedFilter, $request->user(), $payload);

        return redirect()
            ->route($request->string('redirect_route')->toString(), [
                ...$this->savedFilters->queryParameters($filter),
            ])
            ->with('status', 'saved-filter-updated');
    }

    public function destroy(SavedFilter $savedFilter): RedirectResponse
    {
        $this->authorize('delete', $savedFilter);
        $route = match ($savedFilter->entity_type) {
            'lead' => 'leads.index',
            'customer' => 'customers.index',
            'opportunity' => 'pipeline.index',
            'ticket' => 'tickets.index',
            default => 'dashboard',
        };

        $this->savedFilters->delete($savedFilter, request()->user());

        return redirect()
            ->route($route)
            ->with('status', 'saved-filter-deleted');
    }

    public function duplicate(SavedFilter $savedFilter): RedirectResponse
    {
        $this->authorize('duplicate', $savedFilter);

        $copy = $this->savedFilters->duplicate($savedFilter, request()->user());

        $route = match ($copy->entity_type) {
            'lead' => 'leads.index',
            'customer' => 'customers.index',
            'opportunity' => 'pipeline.index',
            'ticket' => 'tickets.index',
            default => 'dashboard',
        };

        return redirect()
            ->route($route, [
                'saved_filter' => $copy->id,
            ])
            ->with('status', 'saved-filter-duplicated');
    }

    public function setDefault(SavedFilter $savedFilter, TenantContext $tenant): RedirectResponse
    {
        $this->authorize('view', $savedFilter);

        $this->savedFilters->setDefault(request()->user(), $tenant->get(), $savedFilter);

        return redirect()
            ->route($this->indexRoute($savedFilter->entity_type), [
                'saved_filter' => $savedFilter->id,
            ])
            ->with('status', 'saved-filter-default-set');
    }

    public function clearDefault(SavedFilter $savedFilter, TenantContext $tenant): RedirectResponse
    {
        $this->authorize('view', $savedFilter);

        $this->savedFilters->clearDefault(request()->user(), $tenant->get(), $savedFilter->entity_type);

        return redirect()
            ->route($this->indexRoute($savedFilter->entity_type), [
                'saved_filter' => $savedFilter->id,
            ])
            ->with('status', 'saved-filter-default-cleared');
    }

    protected function indexRoute(string $entityType): string
    {
        return match ($entityType) {
            'lead' => 'leads.index',
            'customer' => 'customers.index',
            'opportunity' => 'pipeline.index',
            'ticket' => 'tickets.index',
            default => 'dashboard',
        };
    }
}
