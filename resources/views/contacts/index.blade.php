<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Contacts')"
        :subtitle="__('People linked to customer companies')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => __('Contacts'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:filters>
            <form method="GET" action="{{ route('contacts.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <x-forms.input name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('Search name, email, phone…') }}" />
                <x-forms.select name="status" aria-label="{{ __('Status') }}">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (config('contacts.statuses') as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Filter') }}</x-ui.button>
            </form>
        </x-slot:filters>

        @if ($contacts->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset
                    variant="contacts"
                    :title="__('No contacts yet')"
                    :description="__('Open a customer and add people at that company.')"
                    :action-href="route('customers.index')"
                    :action-label="__('Open customers')"
                />
            </x-ui.card>
        @else
            <x-tables.table :columns="[__('Contact'), __('Company'), __('Title'), __('Email'), __('Actions')]" sticky>
                @foreach ($contacts as $contact)
                    <tr class="hover:bg-surface-muted/60">
                        <td class="px-4 py-3">
                            <a href="{{ route('contacts.show', $contact) }}" class="text-sm font-semibold text-ink-heading hover:text-primary-700">{{ $contact->name }}</a>
                            @if ($contact->is_primary)
                                <x-ui.badge variant="primary">{{ __('Primary') }}</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if ($contact->customer)
                                <a href="{{ route('customers.show', $contact->customer) }}" class="text-primary-600 hover:text-primary-700">{{ $contact->customer->display_name }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $contact->title ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $contact->email ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            @can('update', $contact)
                                <a href="{{ route('contacts.show', $contact) }}#email-composer" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ __('Email') }}</a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($contacts->hasPages())
            <x-slot:pagination>
                {{ $contacts->links() }}
            </x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
