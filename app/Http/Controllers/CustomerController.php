<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesSavedIndexFilters;
use App\Http\Requests\IndexCustomerRequest;
use App\Http\Requests\SendCustomerMailRequest;
use App\Http\Requests\StoreCustomerNoteRequest;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Mail\CustomerMail;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\User;
use App\Services\CommercialTimelineService;
use App\Services\CrmActivityService;
use App\Services\CrmEmailService;
use App\Services\CustomerRelationshipService;
use App\Services\CustomerService;
use App\Services\MetadataEntityFormService;
use App\Services\MetadataQueryDefinitionService;
use App\Services\MetadataQueryService;
use App\Services\NoteService;
use App\Services\OrganizationMailer;
use App\Services\RevenueService;
use App\Services\SavedFilterService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CustomerController extends Controller
{
    use AppliesSavedIndexFilters;

    public function __construct(
        protected OrganizationMailer $organizationMailer,
        protected CrmEmailService $crmEmails,
        protected MetadataEntityFormService $metadataForms,
        protected MetadataQueryDefinitionService $metadataDefinitions,
        protected MetadataQueryService $metadataQueries,
        protected SavedFilterService $savedFilters,
        protected CustomerService $customerService,
        protected NoteService $noteService,
    ) {
        $this->authorizeResource(Customer::class, 'customer');
    }

    public function index(IndexCustomerRequest $request, TenantContext $tenant): View|RedirectResponse
    {
        if ($redirect = $this->maybeRedirectToDefaultSavedFilter($request, $tenant, 'customer', $this->savedFilters)) {
            return $redirect;
        }

        $organization = $tenant->get();
        $saved = $this->resolveSavedIndexFilters($request, $tenant, 'customer', $this->savedFilters);
        $filterInput = $saved['input'];

        $query = Customer::query()->with('assignee');
        $this->customerService->applyIndexFilters($query, $filterInput);

        $metadataRequest = $this->metadataDefinitions->requestForWebIndex($organization->id, 'customer', $filterInput);
        $this->metadataQueries->applyForWebIndex($query, $metadataRequest, $organization->id);
        $this->customerService->applyIndexSort($query, $filterInput, (bool) $metadataRequest->sort);

        $metadataFields = $this->metadataDefinitions->webIndexFields($organization->id, 'customer');
        $filters = collect($filterInput)->only([
            'search', 'status', 'type', 'lifecycle_stage', 'segment', 'source', 'industry',
            'assigned_to', 'state', 'country', 'tags', 'created_from', 'created_to',
            'last_activity_from', 'last_activity_to', 'value_min', 'value_max',
            'sort', 'sort_direction', 'metadata_filters', 'metadata_sort',
            'metadata_sort_key', 'metadata_sort_direction', 'saved_filter', 'view',
        ])->all();
        $geographicOptions = $this->customerService->geographicOptions();

        return view('customers.index', [
            'customers' => $query->paginate(15)->withQueryString(),
            'organization' => $organization,
            'assignees' => $this->organizationMembers($organization),
            'filters' => $filters,
            'stateOptions' => $geographicOptions['states'],
            'countryOptions' => $geographicOptions['countries'],
            'metadataFilterFields' => $metadataFields['filterable'],
            'metadataSortFields' => $metadataFields['sortable'],
            'savedFilters' => $saved['savedFilters'],
            'activeSavedFilter' => $saved['activeSavedFilter'],
            'defaultSavedFilter' => $this->savedFilters->defaultFor($request->user(), $organization->id, 'customer'),
            'savedFilterRoute' => 'customers.index',
            'savedFilterEntityType' => 'customer',
        ]);
    }

    public function create(TenantContext $tenant): View
    {
        return view('customers.create', [
            'customer' => new Customer(['status' => 'active']),
            'assignees' => $this->organizationMembers($tenant->get()),
            'metadataFields' => $this->metadataForms->fieldsFor($tenant->get(), 'customer', 'create'),
            'metadataPresenter' => $this->metadataForms->presenter(),
        ]);
    }

    public function store(StoreCustomerRequest $request, TenantContext $tenant): RedirectResponse
    {
        $metadataValues = $this->metadataForms->validatedValuesFromRequest(null, $tenant->get(), 'customer', 'create', $request);

        $customer = $this->customerService->create($request->validated(), $request->user(), $metadataValues);

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', 'customer-created');
    }

    public function show(Customer $customer, TenantContext $tenant, RevenueService $revenue, CommercialTimelineService $timeline, CustomerRelationshipService $relationships): View
    {
        $customer->load(['assignee', 'creator', 'lead', 'notes.user', 'attachments.uploader', 'tasks.assignee', 'primaryContact', 'contacts']);

        $statement = null;
        $user = auth()->user();

        if ($user && ($user->hasPermission('finance.view') || $user->hasPermission('invoices.view'))) {
            $statement = $revenue->customerStatement($customer);
        }

        $hub = $relationships->hub($customer, $user);

        return view('customers.show', [
            'customer' => $customer,
            'organization' => $tenant->get(),
            'statement' => $statement,
            'timelineItems' => $timeline->forCustomer($customer),
            'hub' => $hub,
            'metadataFields' => $this->metadataForms->fieldsFor($tenant->get(), 'customer', 'detail'),
            'metadataPresenter' => $this->metadataForms->presenter(),
        ]);
    }

    public function edit(Customer $customer, TenantContext $tenant): View
    {
        return view('customers.edit', [
            'customer' => $customer,
            'assignees' => $this->organizationMembers($tenant->get()),
            'metadataFields' => $this->metadataForms->fieldsFor($tenant->get(), 'customer', 'edit'),
            'metadataPresenter' => $this->metadataForms->presenter(),
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer, TenantContext $tenant): RedirectResponse
    {
        $metadataValues = $this->metadataForms->validatedValuesFromRequest($customer, $tenant->get(), 'customer', 'edit', $request);

        $this->customerService->update($customer, $request->validated(), $request->user(), $metadataValues);

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', 'customer-updated');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('status', 'customer-deleted');
    }

    public function storeNote(StoreCustomerNoteRequest $request, Customer $customer): RedirectResponse
    {
        $this->noteService->add($customer, $request->validated('body'), $request->user());
        $this->customerService->touchLastActivity($customer);

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', 'customer-note-added');
    }

    public function sendMail(SendCustomerMailRequest $request, Customer $customer, TenantContext $tenant): RedirectResponse
    {
        $organization = $tenant->get();

        if (! $organization) {
            return redirect()
                ->route('customers.show', $customer)
                ->with('error', __('Organization not found.'));
        }

        try {
            $message = $this->crmEmails->send(
                $organization,
                $request->user(),
                $customer,
                $request->validated(),
                new CustomerMail(
                    $customer,
                    $organization,
                    $request->validated('subject'),
                    $request->validated('message'),
                    $request->file('attachments', []) ?? [],
                ),
                $request->file('attachments', []) ?? [],
            );
        } catch (\Throwable $e) {
            return redirect()
                ->route('customers.show', $customer)
                ->with('error', __('Failed to send email: :message', ['message' => $e->getMessage()]));
        }

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', $message->flashKey('customer-email-sent'));
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
