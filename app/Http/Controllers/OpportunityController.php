<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesSavedIndexFilters;
use App\Http\Requests\StoreOpportunityNoteRequest;
use App\Http\Requests\StoreOpportunityRequest;
use App\Http\Requests\UpdateOpportunityRequest;
use App\Http\Requests\UpdateOpportunityStageRequest;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\OpportunityNote;
use App\Models\Organization;
use App\Services\MetadataEntityFormService;
use App\Services\MetadataQueryDefinitionService;
use App\Services\MetadataQueryService;
use App\Services\OpportunityService;
use App\Services\SavedFilterService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OpportunityController extends Controller
{
    use AppliesSavedIndexFilters;

    public function __construct(
        protected MetadataEntityFormService $metadataForms,
        protected MetadataQueryDefinitionService $metadataDefinitions,
        protected MetadataQueryService $metadataQueries,
        protected SavedFilterService $savedFilters,
        protected OpportunityService $opportunityService,
    ) {
        $this->authorizeResource(Opportunity::class, 'opportunity');
    }

    public function index(Request $request, TenantContext $tenant): View
    {
        $organization = $tenant->get();
        $saved = $this->resolveSavedIndexFilters($request, $tenant, 'opportunity', $this->savedFilters);
        $filterInput = $saved['input'];

        $query = Opportunity::query()
            ->with(['customer', 'assignee', 'creator']);

        if ($search = trim((string) ($filterInput['search'] ?? ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('company', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
            });
        }

        if ($stage = ($filterInput['stage'] ?? '')) {
            $query->where('stage', $stage);
        }

        if ($customerId = (int) ($filterInput['customer_id'] ?? 0)) {
            $query->where('customer_id', $customerId);
        }

        if ($assignedTo = (int) ($filterInput['assigned_to'] ?? 0)) {
            $query->where('assigned_to', $assignedTo);
        }

        $metadataRequest = $this->metadataDefinitions->requestForWebIndex($organization->id, 'opportunity', $filterInput);
        $this->metadataQueries->applyForWebIndex($query, $metadataRequest, $organization->id);

        if (! $metadataRequest->sort) {
            $query->latest();
        }

        $metadataFields = $this->metadataDefinitions->webIndexFields($organization->id, 'opportunity');
        $filters = collect($filterInput)->only(['search', 'stage', 'customer_id', 'assigned_to', 'metadata_filters', 'metadata_sort', 'metadata_sort_key', 'metadata_sort_direction', 'saved_filter'])->all();

        return view('pipeline.index', [
            'opportunities' => $query->paginate(15)->withQueryString(),
            'organization' => $organization,
            'customers' => Customer::query()->orderBy('company')->orderBy('name')->get(),
            'assignees' => $this->organizationMembers($organization),
            'filters' => $filters,
            'metadataFilterFields' => $metadataFields['filterable'],
            'metadataSortFields' => $metadataFields['sortable'],
            'savedFilters' => $saved['savedFilters'],
            'activeSavedFilter' => $saved['activeSavedFilter'],
            'savedFilterRoute' => 'pipeline.index',
            'savedFilterEntityType' => 'opportunity',
            'stageCounts' => Opportunity::query()
                ->selectRaw('stage, count(*) as total')
                ->groupBy('stage')
                ->pluck('total', 'stage'),
            'pipelineSummary' => [
                'open_count' => Opportunity::query()->whereIn('stage', config('pipeline.open_stages'))->count(),
                'open_value' => (float) Opportunity::query()
                    ->whereIn('stage', config('pipeline.open_stages'))
                    ->sum('amount'),
                'won_count' => Opportunity::query()->where('stage', 'closed_won')->count(),
                'lost_count' => Opportunity::query()->where('stage', 'closed_lost')->count(),
            ],
        ]);
    }

    public function create(TenantContext $tenant): View
    {
        return view('pipeline.create', [
            'opportunity' => new Opportunity([
                'stage' => 'qualification',
                'currency' => $tenant->get()?->currency ?? 'USD',
            ]),
            'customers' => Customer::query()->orderBy('company')->orderBy('name')->get(),
            'leads' => Lead::query()->whereNotIn('status', ['won', 'lost'])->orderBy('name')->get(),
            'assignees' => $this->organizationMembers($tenant->get()),
            'metadataFields' => $this->metadataForms->fieldsFor($tenant->get(), 'opportunity', 'create'),
            'metadataPresenter' => $this->metadataForms->presenter(),
        ]);
    }

    public function store(StoreOpportunityRequest $request, TenantContext $tenant): RedirectResponse
    {
        $metadataValues = $this->metadataForms->validatedValuesFromRequest(null, $tenant->get(), 'opportunity', 'create', $request);

        $opportunity = Opportunity::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);
        $this->metadataForms->persistValidatedValues($opportunity, $metadataValues);

        return redirect()
            ->route('pipeline.show', $opportunity)
            ->with('status', 'opportunity-created');
    }

    public function show(Opportunity $opportunity): View
    {
        $opportunity->load(['customer', 'lead', 'assignee', 'creator', 'notes.user', 'attachments.uploader', 'tasks.assignee']);

        return view('pipeline.show', [
            'opportunity' => $opportunity,
            'metadataFields' => $this->metadataForms->fieldsFor($opportunity->organization, 'opportunity', 'detail'),
            'metadataPresenter' => $this->metadataForms->presenter(),
        ]);
    }

    public function edit(Opportunity $opportunity, TenantContext $tenant): View
    {
        return view('pipeline.edit', [
            'opportunity' => $opportunity,
            'customers' => Customer::query()->orderBy('company')->orderBy('name')->get(),
            'leads' => Lead::query()->orderBy('name')->get(),
            'assignees' => $this->organizationMembers($tenant->get()),
            'metadataFields' => $this->metadataForms->fieldsFor($tenant->get(), 'opportunity', 'edit'),
            'metadataPresenter' => $this->metadataForms->presenter(),
        ]);
    }

    public function update(UpdateOpportunityRequest $request, Opportunity $opportunity, TenantContext $tenant): RedirectResponse
    {
        $metadataValues = $this->metadataForms->validatedValuesFromRequest($opportunity, $tenant->get(), 'opportunity', 'edit', $request);

        $opportunity->update($request->validated());
        $this->metadataForms->persistValidatedValues($opportunity, $metadataValues);

        return redirect()
            ->route('pipeline.show', $opportunity)
            ->with('status', 'opportunity-updated');
    }

    public function updateStage(UpdateOpportunityStageRequest $request, Opportunity $opportunity): RedirectResponse
    {
        $validated = $request->validated();
        $stage = $validated['stage'];

        $this->opportunityService->updateStage($opportunity, $validated);

        $flashStatus = match ($stage) {
            'closed_won' => 'opportunity-won',
            'closed_lost' => 'opportunity-lost',
            default => 'opportunity-stage-updated',
        };

        return redirect()
            ->route('pipeline.show', $opportunity)
            ->with('status', $flashStatus);
    }

    public function destroy(Opportunity $opportunity): RedirectResponse
    {
        $opportunity->delete();

        return redirect()
            ->route('pipeline.index')
            ->with('status', 'opportunity-deleted');
    }

    public function storeNote(StoreOpportunityNoteRequest $request, Opportunity $opportunity): RedirectResponse
    {
        OpportunityNote::query()->create([
            'organization_id' => $opportunity->organization_id,
            'opportunity_id' => $opportunity->id,
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        return redirect()
            ->route('pipeline.show', $opportunity)
            ->with('status', 'opportunity-note-added');
    }

    protected function organizationMembers(?Organization $organization)
    {
        if (! $organization) {
            return collect();
        }

        return $organization->users()->orderBy('name')->get();
    }
}
