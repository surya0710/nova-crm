<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesSavedIndexFilters;
use App\Http\Requests\SendTicketMailRequest;
use App\Http\Requests\AssignCustomerTicketRequest;
use App\Http\Requests\IndexCustomerTicketRequest;
use App\Http\Requests\StoreCustomerTicketNoteRequest;
use App\Http\Requests\StoreCustomerTicketRequest;
use App\Http\Requests\TransitionCustomerTicketRequest;
use App\Http\Requests\UpdateCustomerTicketRequest;
use App\Models\Customer;
use App\Models\CustomerTicket;
use App\Models\Organization;
use App\Models\User;
use App\Services\CommercialTimelineService;
use App\Services\CrmEmailService;
use App\Services\CustomerTicketService;
use App\Services\SavedFilterService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CustomerTicketController extends Controller
{
    use AppliesSavedIndexFilters;

    public function __construct(
        protected CustomerTicketService $tickets,
        protected SavedFilterService $savedFilters,
        protected CommercialTimelineService $timeline,
        protected CrmEmailService $crmEmails,
    ) {
        $this->authorizeResource(CustomerTicket::class, 'ticket');
    }

    public function index(IndexCustomerTicketRequest $request, TenantContext $tenant): View|RedirectResponse
    {
        if ($redirect = $this->maybeRedirectToDefaultSavedFilter($request, $tenant, 'ticket', $this->savedFilters)) {
            return $redirect;
        }

        $saved = $this->resolveSavedIndexFilters($request, $tenant, 'ticket', $this->savedFilters);
        $filters = $saved['input'];
        $query = CustomerTicket::query()->with(['customer', 'contact', 'assignee']);
        $this->tickets->applyIndexFilters($query, $filters);
        $this->tickets->applyIndexSort($query, $filters);

        return view('tickets.index', [
            'tickets' => $query->paginate(15)->withQueryString(),
            'filters' => collect($filters)->only([
                'search', 'status', 'priority', 'customer_id', 'contact_id', 'assigned_to',
                'overdue', 'unassigned', 'sort', 'sort_direction', 'saved_filter',
            ])->all(),
            'metrics' => $this->tickets->metrics(),
            'customers' => Customer::query()->orderBy('company')->orderBy('name')->limit(200)->get(),
            'assignees' => $this->organizationMembers($tenant->get()),
            'savedFilters' => $saved['savedFilters'],
            'activeSavedFilter' => $saved['activeSavedFilter'],
            'savedFilterRoute' => 'tickets.index',
            'savedFilterEntityType' => 'ticket',
        ]);
    }

    public function create(Customer $customer, TenantContext $tenant): View
    {
        $this->authorize('create', [CustomerTicket::class, $customer]);

        return view('tickets.create', [
            'customer' => $customer->load('contacts'),
            'ticket' => new CustomerTicket(['status' => 'open', 'priority' => 'medium', 'customer_id' => $customer->id]),
            'assignees' => $this->organizationMembers($tenant->get()),
        ]);
    }

    public function store(StoreCustomerTicketRequest $request, Customer $customer): RedirectResponse
    {
        $ticket = $this->tickets->create($customer, $request->validated(), $request->user());

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'ticket-created');
    }

    public function show(CustomerTicket $ticket): View
    {
        $ticket->load(['customer.contacts', 'contact', 'assignee', 'creator', 'notes.user']);

        return view('tickets.show', [
            'ticket' => $ticket,
            'organization' => $ticket->organization,
            'timeline' => $this->timeline->forCustomer($ticket->customer, 30),
            'assignees' => $this->organizationMembers($ticket->organization),
        ]);
    }

    public function edit(CustomerTicket $ticket, TenantContext $tenant): View
    {
        $ticket->load(['customer.contacts']);

        return view('tickets.edit', [
            'ticket' => $ticket,
            'customer' => $ticket->customer,
            'assignees' => $this->organizationMembers($tenant->get()),
        ]);
    }

    public function update(UpdateCustomerTicketRequest $request, CustomerTicket $ticket): RedirectResponse
    {
        $this->tickets->update($ticket, $request->validated(), $request->user());

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'ticket-updated');
    }

    public function destroy(CustomerTicket $ticket): RedirectResponse
    {
        $customer = $ticket->customer;
        $ticket->delete();

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', 'ticket-deleted');
    }

    public function storeNote(StoreCustomerTicketNoteRequest $request, CustomerTicket $ticket): RedirectResponse
    {
        $this->tickets->addNote($ticket, $request->validated('body'), $request->user());

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'ticket-note-added');
    }

    public function assign(AssignCustomerTicketRequest $request, CustomerTicket $ticket): RedirectResponse
    {
        $this->tickets->assign($ticket, $request->validated('assigned_to'), $request->user());

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'ticket-assigned');
    }

    public function reopen(CustomerTicket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);
        $this->tickets->reopen($ticket, request()->user());

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'ticket-reopened');
    }

    public function transition(TransitionCustomerTicketRequest $request, CustomerTicket $ticket): RedirectResponse
    {
        $this->tickets->transition($ticket, $request->validated('status'), $request->user());

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'ticket-updated');
    }

    public function sendMail(SendTicketMailRequest $request, CustomerTicket $ticket, TenantContext $tenant): RedirectResponse
    {
        $organization = $tenant->get() ?? $ticket->organization;

        if (! $organization) {
            return redirect()
                ->route('tickets.show', $ticket)
                ->with('error', __('Organization not found.'));
        }

        try {
            $message = $this->crmEmails->send(
                $organization,
                $request->user(),
                $ticket,
                $request->validated(),
                attachments: $request->file('attachments', []) ?? [],
            );
        } catch (\Throwable $e) {
            return redirect()
                ->route('tickets.show', $ticket)
                ->with('error', __('Failed to send email: :message', ['message' => $e->getMessage()]));
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', $message->flashKey('ticket-email-sent'));
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
