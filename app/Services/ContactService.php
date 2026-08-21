<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactNote;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContactService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Customer $customer, array $data, User $user): Contact
    {
        return DB::transaction(function () use ($customer, $data, $user) {
            $contact = Contact::query()->create([
                ...$data,
                'organization_id' => $customer->organization_id,
                'customer_id' => $customer->id,
                'created_by' => $user->id,
            ]);

            if ($contact->is_primary || $customer->contacts()->count() === 1) {
                $this->markPrimary($contact);
            }

            return $contact->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Contact $contact, array $data): Contact
    {
        return DB::transaction(function () use ($contact, $data) {
            $contact->update($data);

            if ($contact->is_primary) {
                $this->markPrimary($contact);
            }

            return $contact->fresh();
        });
    }

    public function markPrimary(Contact $contact): Contact
    {
        Contact::query()
            ->where('customer_id', $contact->customer_id)
            ->whereKeyNot($contact->id)
            ->update(['is_primary' => false]);

        if (! $contact->is_primary) {
            $contact->update(['is_primary' => true]);
        }

        $this->syncCustomerParty($contact->fresh());

        return $contact->fresh();
    }

    public function addNote(Contact $contact, string $body, User $actor): ContactNote
    {
        if (trim($body) === '') {
            throw ValidationException::withMessages(['body' => __('A note body is required.')]);
        }

        $note = ContactNote::query()->create([
            'organization_id' => $contact->organization_id,
            'contact_id' => $contact->id,
            'user_id' => $actor->id,
            'body' => $body,
        ]);

        app(CustomerService::class)->touchLastActivity($contact->customer);

        return $note;
    }

    /**
     * Seed a primary contact from the customer party fields (create path).
     */
    public function seedPrimaryFromCustomer(Customer $customer, User $user): ?Contact
    {
        if ($customer->contacts()->exists()) {
            return $customer->primaryContact;
        }

        $name = trim((string) $customer->name);
        if ($name === '') {
            return null;
        }

        return $this->create($customer, [
            'name' => $name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'is_primary' => true,
            'status' => 'active',
        ], $user);
    }

    protected function syncCustomerParty(Contact $contact): void
    {
        $customer = $contact->customer;
        if (! $customer) {
            return;
        }

        $customer->update([
            'name' => $contact->name,
            'email' => $contact->email,
            'phone' => $contact->phone,
        ]);
    }
}
