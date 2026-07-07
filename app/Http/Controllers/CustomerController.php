<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendCustomerMailRequest;
use App\Http\Requests\StoreCustomerNoteRequest;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Mail\CustomerMail;
use App\Models\Customer;
use App\Models\CustomerNote;
use App\Models\User;
use App\Services\OrganizationMailer;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(protected OrganizationMailer $organizationMailer)
    {
        $this->authorizeResource(Customer::class, 'customer');
    }

    public function index(Request $request, TenantContext $tenant): View
    {
        $organization = $tenant->get();

        $query = Customer::query()
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

        if ($industry = $request->string('industry')->trim()->toString()) {
            $query->where('industry', 'like', "%{$industry}%");
        }

        if ($assignedTo = $request->integer('assigned_to')) {
            $query->where('assigned_to', $assignedTo);
        }

        return view('customers.index', [
            'customers' => $query->paginate(15)->withQueryString(),
            'organization' => $organization,
            'assignees' => $this->organizationMembers($organization),
            'filters' => $request->only(['search', 'status', 'industry', 'assigned_to']),
        ]);
    }

    public function create(TenantContext $tenant): View
    {
        return view('customers.create', [
            'customer' => new Customer(['status' => 'active']),
            'assignees' => $this->organizationMembers($tenant->get()),
        ]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $customer = Customer::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', 'customer-created');
    }

    public function show(Customer $customer, TenantContext $tenant): View
    {
        $customer->load(['assignee', 'creator', 'lead', 'notes.user', 'attachments.uploader', 'tasks.assignee']);

        return view('customers.show', [
            'customer' => $customer,
            'organization' => $tenant->get(),
        ]);
    }

    public function edit(Customer $customer, TenantContext $tenant): View
    {
        return view('customers.edit', [
            'customer' => $customer,
            'assignees' => $this->organizationMembers($tenant->get()),
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

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
        CustomerNote::query()->create([
            'organization_id' => $customer->organization_id,
            'customer_id' => $customer->id,
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

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

        if (! $this->organizationMailer->isConfigured($organization)) {
            return redirect()
                ->route('customers.show', $customer)
                ->with('error', __('Configure organization email in Settings → Email before sending.'));
        }

        try {
            $this->organizationMailer->send(
                $organization,
                $request->validated('email'),
                new CustomerMail(
                    $customer,
                    $organization,
                    $request->validated('subject'),
                    $request->validated('message'),
                    $request->file('attachments', []),
                ),
            );
        } catch (\Throwable $e) {
            return redirect()
                ->route('customers.show', $customer)
                ->with('error', __('Failed to send email: :message', ['message' => $e->getMessage()]));
        }

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', 'customer-email-sent');
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
}
