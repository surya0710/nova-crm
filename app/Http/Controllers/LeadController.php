<?php

namespace App\Http\Controllers;

use App\Exceptions\DuplicateCustomerException;
use App\Http\Requests\ConvertLeadRequest;
use App\Http\Requests\StoreLeadNoteRequest;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadFollowUpRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Http\Requests\UpdateLeadStatusRequest;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\Organization;
use App\Models\User;
use App\Services\LeadConversionService;
use App\Services\LeadFollowUpService;
use App\Services\LeadService;
use App\Services\MetadataFormResolver;
use App\Services\MetadataFormValuePresenter;
use App\Services\MetadataValueStorageService;
use App\Services\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class LeadController extends Controller
{
    public function __construct(
        protected LeadFollowUpService $followUpService,
        protected LeadConversionService $conversionService,
        protected LeadService $leadService,
        protected MetadataFormResolver $metadataFormResolver,
        protected MetadataFormValuePresenter $metadataPresenter,
        protected MetadataValueStorageService $metadataValueStorage,
    ) {
        $this->authorizeResource(Lead::class, 'lead');
    }

    public function index(Request $request, TenantContext $tenant): View
    {
        $organization = $tenant->get();

        $query = Lead::query()
            ->with(['assignee', 'creator'])
            ->latest();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($source = $request->string('source')->toString()) {
            $query->where('source', $source);
        }

        if ($priority = $request->string('priority')->toString()) {
            $query->where('priority', $priority);
        }

        if ($assignedTo = $request->integer('assigned_to')) {
            $query->where('assigned_to', $assignedTo);
        }

        return view('leads.index', [
            'leads' => $query->paginate(15)->withQueryString(),
            'organization' => $organization,
            'assignees' => $this->organizationMembers($organization),
            'filters' => $request->only(['search', 'status', 'source', 'priority', 'assigned_to']),
        ]);
    }

    public function create(TenantContext $tenant): View
    {
        return view('leads.create', [
            'lead' => new Lead(['status' => 'new', 'priority' => 'medium', 'source' => 'manual_entry']),
            'assignees' => $this->organizationMembers($tenant->get()),
            'metadataFields' => $this->metadataFields($tenant->get(), 'create'),
            'metadataPresenter' => $this->metadataPresenter,
        ]);
    }

    public function store(StoreLeadRequest $request, TenantContext $tenant): RedirectResponse
    {
        $validated = $this->followUpService->normalizeValidatedFollowUp($request->validated());

        $lead = $this->leadService->create($validated, $request->user());
        $this->storeMetadataValues($lead, $tenant->get(), 'create', $request);

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
            'metadataFields' => $this->metadataFields($lead->organization, 'detail'),
            'metadataPresenter' => $this->metadataPresenter,
        ]);
    }

    public function edit(Lead $lead, TenantContext $tenant): View
    {
        return view('leads.edit', [
            'lead' => $lead,
            'assignees' => $this->organizationMembers($tenant->get()),
            'metadataFields' => $this->metadataFields($tenant->get(), 'edit'),
            'metadataPresenter' => $this->metadataPresenter,
        ]);
    }

    public function update(UpdateLeadRequest $request, Lead $lead, TenantContext $tenant): RedirectResponse
    {
        $lead->update($this->followUpService->normalizeValidatedFollowUp($request->validated()));
        $this->storeMetadataValues($lead, $tenant->get(), 'edit', $request);

        return redirect()
            ->route('leads.show', $lead)
            ->with('status', 'lead-updated');
    }

    public function updateStatus(UpdateLeadStatusRequest $request, Lead $lead): RedirectResponse
    {
        $lead->update(['status' => $request->validated('status')]);

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
        LeadNote::query()->create([
            'organization_id' => $lead->organization_id,
            'lead_id' => $lead->id,
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

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
     * @return \Illuminate\Support\Collection<int, User>
     */
    protected function organizationMembers(?\App\Models\Organization $organization)
    {
        if (! $organization) {
            return collect();
        }

        return $organization->users()->orderBy('name')->get();
    }

    protected function metadataFields(?Organization $organization, string $context)
    {
        if (! $organization) {
            return collect();
        }

        return $this->metadataFormResolver->fieldsFor($organization, 'lead', $context);
    }

    protected function storeMetadataValues(Lead $lead, ?Organization $organization, string $context, Request $request): void
    {
        if (! $organization) {
            return;
        }

        $payload = $request->input('custom_fields', []);
        $payload = is_array($payload) ? $payload : [];

        $values = $this->metadataPresenter->extractSubmittedValues(
            $this->metadataFields($organization, $context),
            $payload,
        );

        if ($values !== []) {
            $this->metadataValueStorage->mergeValues($lead, $values);
        }
    }
}
