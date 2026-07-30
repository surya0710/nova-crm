<?php

namespace App\Http\Controllers;

use App\Exceptions\DuplicateCustomerException;
use App\Http\Controllers\Concerns\AppliesSavedIndexFilters;
use App\Http\Requests\ConvertLeadRequest;
use App\Http\Requests\IndexLeadRequest;
use App\Http\Requests\StoreLeadNoteRequest;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadFollowUpRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Http\Requests\UpdateLeadStatusRequest;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use App\Services\Bulk\BulkOperationsService;
use App\Services\LeadConversionService;
use App\Services\LeadFollowUpService;
use App\Services\LeadService;
use App\Services\MetadataEntityFormService;
use App\Services\MetadataQueryDefinitionService;
use App\Services\MetadataQueryService;
use App\Services\NoteService;
use App\Services\SavedFilterService;
use App\Services\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class LeadController extends Controller
{
    use AppliesSavedIndexFilters;

    public function __construct(
        protected LeadFollowUpService $followUpService,
        protected LeadConversionService $conversionService,
        protected LeadService $leadService,
        protected MetadataEntityFormService $metadataForms,
        protected MetadataQueryDefinitionService $metadataDefinitions,
        protected MetadataQueryService $metadataQueries,
        protected SavedFilterService $savedFilters,
        protected NoteService $noteService,
        protected BulkOperationsService $bulkOperations,
    ) {
        $this->authorizeResource(Lead::class, 'lead');
    }

    public function index(IndexLeadRequest $request, TenantContext $tenant): View
    {
        $organization = $tenant->get();
        $saved = $this->resolveSavedIndexFilters($request, $tenant, 'lead', $this->savedFilters);
        $filterInput = $saved['input'];

        $query = Lead::query()->with('assignee');

        $this->leadService->searchQuery($query, $filterInput['search'] ?? null);
        $this->leadService->geographicFilterQuery(
            $query,
            $filterInput['state'] ?? null,
            $filterInput['country'] ?? null,
        );

        if ($status = ($filterInput['status'] ?? '')) {
            $query->where('status', $status);
        }

        if ($source = ($filterInput['source'] ?? '')) {
            $query->where('source', $source);
        }

        if ($priority = ($filterInput['priority'] ?? '')) {
            $query->where('priority', $priority);
        }

        if ($assignedTo = (int) ($filterInput['assigned_to'] ?? 0)) {
            $query->where('assigned_to', $assignedTo);
        }

        $metadataRequest = $this->metadataDefinitions->requestForWebIndex($organization->id, 'lead', $filterInput);
        $this->metadataQueries->applyForWebIndex($query, $metadataRequest, $organization->id);

        if (! $metadataRequest->sort) {
            $query->latest();
        }

        $metadataFields = $this->metadataDefinitions->webIndexFields($organization->id, 'lead');
        $filters = collect($filterInput)->only(['search', 'status', 'source', 'priority', 'assigned_to', 'state', 'country', 'metadata_filters', 'metadata_sort', 'metadata_sort_key', 'metadata_sort_direction', 'saved_filter'])->all();
        $leads = $query->paginate(15)->withQueryString();
        $geographicOptions = $this->leadService->geographicOptions();

        return view('leads.index', [
            'leads' => $leads,
            'organization' => $organization,
            'assignees' => $this->organizationMembers($organization),
            'filters' => $filters,
            'stateOptions' => $geographicOptions['states'],
            'countryOptions' => $geographicOptions['countries'],
            'metadataFilterFields' => $metadataFields['filterable'],
            'metadataSortFields' => $metadataFields['sortable'],
            'savedFilters' => $saved['savedFilters'],
            'activeSavedFilter' => $saved['activeSavedFilter'],
            'savedFilterRoute' => 'leads.index',
            'savedFilterEntityType' => 'lead',
            'bulkActions' => $this->bulkOperations->availableActionsFor(
                $request->user(),
                $organization,
                'lead'
            ),
        ]);
    }

    public function create(TenantContext $tenant): View
    {
        return view('leads.create', [
            'lead' => new Lead(['status' => 'new', 'priority' => 'medium', 'source' => 'manual_entry']),
            'assignees' => $this->organizationMembers($tenant->get()),
            'metadataFields' => $this->metadataForms->fieldsFor($tenant->get(), 'lead', 'create'),
            'metadataPresenter' => $this->metadataForms->presenter(),
        ]);
    }

    public function store(StoreLeadRequest $request, TenantContext $tenant): RedirectResponse
    {
        $validated = $this->followUpService->normalizeValidatedFollowUp($request->validated());
        $metadataValues = $this->metadataForms->validatedValuesFromRequest(null, $tenant->get(), 'lead', 'create', $request);

        $lead = $this->leadService->create(
            $validated,
            $request->user(),
            metadataValues: $metadataValues,
        );

        return redirect()
            ->route('leads.show', $lead)
            ->with('status', 'lead-created');
    }

    public function show(Lead $lead): View
    {
        if ($lead->needsFollowUpAlert()) {
            $lead->update(['follow_up_alerted_at' => now()]);
        }

        $lead->load(['assignee', 'creator', 'notes.user', 'attachments.uploader', 'tasks.assignee']);

        return view('leads.show', [
            'lead' => $lead,
            'metadataFields' => $this->metadataForms->fieldsFor($lead->organization, 'lead', 'detail'),
            'metadataPresenter' => $this->metadataForms->presenter(),
        ]);
    }

    public function edit(Lead $lead, TenantContext $tenant): View
    {
        return view('leads.edit', [
            'lead' => $lead,
            'assignees' => $this->organizationMembers($tenant->get()),
            'metadataFields' => $this->metadataForms->fieldsFor($tenant->get(), 'lead', 'edit'),
            'metadataPresenter' => $this->metadataForms->presenter(),
        ]);
    }

    public function update(UpdateLeadRequest $request, Lead $lead, TenantContext $tenant): RedirectResponse
    {
        $metadataValues = $this->metadataForms->validatedValuesFromRequest($lead, $tenant->get(), 'lead', 'edit', $request);

        $this->leadService->update(
            $lead,
            $this->followUpService->normalizeValidatedFollowUp($request->validated()),
            $request->user(),
            $metadataValues,
        );

        return redirect()
            ->route('leads.show', $lead)
            ->with('status', 'lead-updated');
    }

    public function updateStatus(UpdateLeadStatusRequest $request, Lead $lead): RedirectResponse
    {
        $this->leadService->changeStatus($lead, $request->validated('status'), $request->user());

        return redirect()
            ->route('leads.show', $lead)
            ->with('status', 'lead-status-updated');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return redirect()
            ->route('leads.index')
            ->with('status', 'lead-deleted');
    }

    public function storeNote(StoreLeadNoteRequest $request, Lead $lead): RedirectResponse
    {
        $this->noteService->add($lead, $request->validated('body'), $request->user());

        return redirect()
            ->route('leads.show', $lead)
            ->with('status', 'lead-note-added');
    }

    public function convert(ConvertLeadRequest $request, Lead $lead): RedirectResponse
    {
        $this->authorize('convert', $lead);

        try {
            $result = $this->conversionService->convert(
                $lead,
                $request->validated(),
                $request->user(),
            );
        } catch (DuplicateCustomerException $e) {
            return redirect()
                ->route('leads.show', $lead)
                ->withInput()
                ->with('duplicate_customers', $e->duplicateCustomers->map(fn ($customer) => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'company' => $customer->company,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                ])->all())
                ->withErrors($e->errors());
        } catch (AuthorizationException $e) {
            return redirect()
                ->route('leads.show', $lead)
                ->with('error', $e->getMessage());
        } catch (ValidationException $e) {
            return redirect()
                ->route('leads.show', $lead)
                ->withInput()
                ->withErrors($e->errors());
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('leads.show', $lead)
                ->with('error', __('Lead conversion failed. Please try again.'));
        }

        $flashStatus = $result['opportunity']
            ? 'lead-converted-with-opportunity'
            : 'lead-converted';

        return redirect()
            ->route('customers.show', $result['customer'])
            ->with('status', $flashStatus);
    }

    public function dueFollowUps(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission('leads.view'), 403);

        return response()->json([
            'data' => $this->followUpService->dueForAlertPayloads(),
        ]);
    }

    public function acknowledgeFollowUp(Request $request, Lead $lead): JsonResponse
    {
        $this->authorize('update', $lead);

        $lead->update(['follow_up_alerted_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function updateFollowUp(UpdateLeadFollowUpRequest $request, Lead $lead): RedirectResponse
    {
        $validated = $this->followUpService->normalizeValidatedFollowUp($request->validated());

        $lead->update([
            'next_follow_up_at' => $validated['next_follow_up_at'] ?? null,
            'next_follow_up_note' => $validated['next_follow_up_note'] ?? null,
        ]);

        return redirect()
            ->route('leads.show', $lead)
            ->with('status', 'lead-follow-up-updated');
    }

    /**
     * @return Collection<int, User>
     */
    protected function organizationMembers(?Organization $organization)
    {
        if (! $organization) {
            return collect();
        }

        return $organization->users()->orderBy('name')->get();
    }
}
