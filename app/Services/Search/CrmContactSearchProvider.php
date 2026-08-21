<?php

namespace App\Services\Search;

use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;

class CrmContactSearchProvider implements SearchProviderInterface
{
    public function key(): string
    {
        return 'contacts';
    }

    public function label(): string
    {
        return __('Contacts');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        if (! $user->hasPermission('customers.view')) {
            return collect();
        }

        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        $like = '%'.mb_strtolower($query).'%';

        return Contact::query()
            ->with('customer')
            ->where('organization_id', $organization->id)
            ->where(function ($inner) use ($like) {
                $inner->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(phone) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(whatsapp) LIKE ?', [$like]);
            })
            ->limit($limit)
            ->get()
            ->map(fn (Contact $contact) => [
                'type' => __('Contact'),
                'label' => $this->label(),
                'title' => $contact->name,
                'subtitle' => collect([$contact->customer?->display_name, $contact->email])->filter()->implode(' · '),
                'url' => route('contacts.show', $contact),
                'workspace' => 'crm',
            ]);
    }
}
