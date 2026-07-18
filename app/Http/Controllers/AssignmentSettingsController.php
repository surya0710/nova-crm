<?php

namespace App\Http\Controllers;

use App\Models\AssignmentPool;
use App\Models\AssignmentRule;
use App\Models\Organization;
use App\Services\Assignment\AssignmentConfigurationService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssignmentSettingsController extends Controller
{
    public function __construct(
        protected AssignmentConfigurationService $configuration,
    ) {}

    public function index(TenantContext $tenant): View
    {
        $organization = $this->organization($tenant);

        $pools = AssignmentPool::query()
            ->with(['members.user'])
            ->orderBy('name')
            ->get();

        $rules = AssignmentRule::query()
            ->with(['pool'])
            ->orderBy('entity_type')
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        $members = $organization->users()->orderBy('name')->get();

        return view('assignments.index', [
            'organization' => $organization,
            'pools' => $pools,
            'rules' => $rules,
            'members' => $members,
            'strategies' => config('assignment.strategy_labels', []),
            'entityTypes' => config('assignment.entity_types', []),
            'sources' => config('leads.sources', []),
            'statuses' => config('leads.statuses', []),
        ]);
    }

    public function storePool(Request $request, TenantContext $tenant): RedirectResponse
    {
        $organization = $this->organization($tenant);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'strategy' => ['required', 'string', 'in:'.implode(',', array_keys(config('assignment.strategies', [])))],
            'is_active' => ['sometimes', 'boolean'],
            'members' => ['nullable', 'array'],
            'members.*.user_id' => ['required', 'integer'],
            'members.*.weight' => ['nullable', 'integer', 'min:1', 'max:100'],
            'members.*.is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['members'] = $this->normalizeMembers($validated['members'] ?? []);

        $this->configuration->createPool($organization, $validated, $request->user());

        return redirect()
            ->route('assignments.index')
            ->with('status', __('Assignment pool created.'));
    }

    public function updatePool(Request $request, AssignmentPool $pool, TenantContext $tenant): RedirectResponse
    {
        $this->assertSameOrganization($pool->organization_id, $tenant);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'strategy' => ['required', 'string', 'in:'.implode(',', array_keys(config('assignment.strategies', [])))],
            'is_active' => ['sometimes', 'boolean'],
            'members' => ['nullable', 'array'],
            'members.*.user_id' => ['required', 'integer'],
            'members.*.weight' => ['nullable', 'integer', 'min:1', 'max:100'],
            'members.*.is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['members'] = $this->normalizeMembers($validated['members'] ?? []);

        $this->configuration->updatePool($pool, $validated, $request->user());

        return redirect()
            ->route('assignments.index')
            ->with('status', __('Assignment pool updated.'));
    }

    public function storeRule(Request $request, TenantContext $tenant): RedirectResponse
    {
        $organization = $this->organization($tenant);

        $validated = $this->validateRule($request);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_default'] = $request->boolean('is_default');
        $validated['conditions'] = $this->normalizeConditions($validated);

        $this->configuration->createRule($organization, $validated, $request->user());

        return redirect()
            ->route('assignments.index')
            ->with('status', __('Assignment rule created.'));
    }

    public function updateRule(Request $request, AssignmentRule $rule, TenantContext $tenant): RedirectResponse
    {
        $this->assertSameOrganization($rule->organization_id, $tenant);

        $validated = $this->validateRule($request);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_default'] = $request->boolean('is_default');
        $validated['conditions'] = $this->normalizeConditions($validated);

        $this->configuration->updateRule($rule, $validated, $request->user());

        return redirect()
            ->route('assignments.index')
            ->with('status', __('Assignment rule updated.'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateRule(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'entity_type' => ['required', 'string', 'in:'.implode(',', array_keys(config('assignment.entity_types', [])))],
            'priority' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'strategy' => ['nullable', 'string', 'in:'.implode(',', array_keys(config('assignment.strategies', [])))],
            'assignment_pool_id' => ['nullable', 'integer'],
            'source' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'lead_type' => ['nullable', 'string', 'max:100'],
            'pipeline' => ['nullable', 'string', 'max:100'],
            'metadata_key' => ['nullable', 'string', 'max:100'],
            'metadata_value' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function normalizeConditions(array $validated): array
    {
        $conditions = [];

        foreach (['source', 'status', 'country', 'lead_type', 'pipeline'] as $field) {
            if (! empty($validated[$field])) {
                $conditions[$field] = $validated[$field];
            }
        }

        if (! empty($validated['metadata_key'])) {
            $conditions['metadata'] = [
                $validated['metadata_key'] => $validated['metadata_value'] ?? '',
            ];
        }

        return $conditions;
    }

    /**
     * @param  list<array{user_id: int|string, weight?: int|string, is_active?: bool|string}>  $members
     * @return list<array{user_id: int, weight: int, is_active: bool}>
     */
    protected function normalizeMembers(array $members): array
    {
        $normalized = [];

        foreach ($members as $member) {
            if (empty($member['user_id'])) {
                continue;
            }

            $normalized[] = [
                'user_id' => (int) $member['user_id'],
                'weight' => max(1, (int) ($member['weight'] ?? 1)),
                'is_active' => filter_var($member['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        return $normalized;
    }

    protected function organization(TenantContext $tenant): Organization
    {
        $organization = $tenant->get();

        abort_unless($organization, 404);

        return $organization;
    }

    protected function assertSameOrganization(int $organizationId, TenantContext $tenant): void
    {
        abort_unless($tenant->id() === $organizationId, 404);
    }
}
