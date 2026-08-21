<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendContactMailRequest;
use App\Http\Requests\StoreContactNoteRequest;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\StoreCrmActivityRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Models\Contact;
use App\Models\Customer;
use App\Services\CommercialTimelineService;
use App\Services\ContactService;
use App\Services\CrmActivityService;
use App\Services\CrmEmailService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function __construct(
        protected ContactService $contacts,
        protected CrmActivityService $activities,
        protected CommercialTimelineService $timeline,
        protected CrmEmailService $crmEmails,
    ) {
        $this->authorizeResource(Contact::class, 'contact');
    }

    public function index(Request $request, TenantContext $tenant): View
    {
        $query = Contact::query()->with(['customer', 'creator']);

        if ($search = trim((string) $request->input('search'))) {
            $like = '%'.mb_strtolower($search).'%';
            $query->where(function ($inner) use ($like) {
                $inner->whereRaw('LOWER(contacts.name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(contacts.email) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(contacts.phone) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(contacts.title) LIKE ?', [$like]);
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return view('contacts.index', [
            'contacts' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'status']),
            'organization' => $tenant->get(),
        ]);
    }

    public function create(Customer $customer): View
    {
        $this->authorize('create', [Contact::class, $customer]);

        return view('contacts.create', [
            'customer' => $customer,
            'contact' => new Contact(['status' => 'active', 'customer_id' => $customer->id]),
        ]);
    }

    public function store(StoreContactRequest $request, Customer $customer): RedirectResponse
    {
        $contact = $this->contacts->create($customer, $request->validated(), $request->user());

        return redirect()
            ->route('contacts.show', $contact)
            ->with('status', 'contact-created');
    }

    public function show(Contact $contact): View
    {
        $contact->load(['customer', 'creator', 'notes.user', 'tasks.assignee', 'activities.creator']);

        return view('contacts.show', [
            'contact' => $contact,
            'timelineItems' => $this->timeline->forContact($contact),
            'organization' => $contact->customer?->organization,
        ]);
    }

    public function edit(Contact $contact): View
    {
        return view('contacts.edit', [
            'contact' => $contact->load('customer'),
            'customer' => $contact->customer,
        ]);
    }

    public function update(UpdateContactRequest $request, Contact $contact): RedirectResponse
    {
        $this->contacts->update($contact, $request->validated());

        return redirect()
            ->route('contacts.show', $contact)
            ->with('status', 'contact-updated');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $customer = $contact->customer;
        $contact->delete();

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', 'contact-deleted');
    }

    public function storeNote(StoreContactNoteRequest $request, Contact $contact): RedirectResponse
    {
        $this->contacts->addNote($contact, $request->validated('body'), $request->user());

        return redirect()
            ->route('contacts.show', $contact)
            ->with('status', 'contact-note-added');
    }

    public function storeActivity(StoreCrmActivityRequest $request, Contact $contact): RedirectResponse
    {
        $this->activities->createForContact($contact, $request->validated(), $request->user());

        return redirect()
            ->route('contacts.show', $contact)
            ->with('status', 'contact-activity-logged');
    }

    public function sendMail(SendContactMailRequest $request, Contact $contact, TenantContext $tenant): RedirectResponse
    {
        $organization = $tenant->get() ?? $contact->organization;

        if (! $organization) {
            return redirect()
                ->route('contacts.show', $contact)
                ->with('error', __('Organization not found.'));
        }

        try {
            $message = $this->crmEmails->send(
                $organization,
                $request->user(),
                $contact,
                $request->validated(),
                attachments: $request->file('attachments', []) ?? [],
            );
        } catch (\Throwable $e) {
            return redirect()
                ->route('contacts.show', $contact)
                ->with('error', __('Failed to send email: :message', ['message' => $e->getMessage()]));
        }

        return redirect()
            ->route('contacts.show', $contact)
            ->with('status', $message->flashKey('contact-email-sent'));
    }
}
