@props(['lead'])

@php
    $duplicateCustomers = session('duplicate_customers', []);
    $hasDuplicates = count($duplicateCustomers) > 0;
    $showConvertModal = $hasDuplicates
        ? false
        : ($errors->hasAny(['name', 'email', 'phone', 'create_opportunity']) && ! $hasDuplicates);
    $showDuplicateModal = $hasDuplicates || $errors->has('existing_customer_id');
    $canCreateOpportunity = auth()->user()->can('create', \App\Models\Opportunity::class);
@endphp

{{-- Initial conversion modal --}}
<x-modal name="convert-lead" :show="$showConvertModal" focusable>
    <form method="POST" action="{{ route('leads.convert', $lead) }}" class="p-6">
        @csrf

        <h2 class="text-lg font-medium text-gray-900">{{ __('Convert Lead') }}</h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ __('Create a customer from this lead. You can optionally create a pipeline opportunity at the same time.') }}
        </p>

        <div class="mt-6 space-y-4">
            <div>
                <x-input-label for="convert_name" :value="__('Customer Name')" />
                <x-text-input
                    id="convert_name"
                    name="name"
                    type="text"
                    class="block mt-1 w-full"
                    :value="old('name', $lead->name)"
                    required
                />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="convert_email" :value="__('Email')" />
                <x-text-input
                    id="convert_email"
                    name="email"
                    type="email"
                    class="block mt-1 w-full"
                    :value="old('email', $lead->email)"
                />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="convert_phone" :value="__('Phone')" />
                <x-text-input
                    id="convert_phone"
                    name="phone"
                    type="text"
                    class="block mt-1 w-full"
                    :value="old('phone', $lead->phone)"
                />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>

            @if ($canCreateOpportunity)
                <div class="flex items-center gap-2">
                    <input type="hidden" name="create_opportunity" value="0">
                    <input
                        id="create_opportunity"
                        name="create_opportunity"
                        type="checkbox"
                        value="1"
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        @checked(old('create_opportunity', true))
                    />
                    <x-input-label for="create_opportunity" :value="__('Create Opportunity')" class="!mb-0" />
                </div>
                <x-input-error :messages="$errors->get('create_opportunity')" class="mt-2" />
            @else
                <input type="hidden" name="create_opportunity" value="0">
            @endif

            <x-input-error :messages="$errors->get('lead')" class="mt-2" />
            <x-input-error :messages="$errors->get('duplicate_customer')" class="mt-2" />
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <x-secondary-button type="button" x-on:click="$dispatch('close-modal', 'convert-lead')">
                {{ __('Cancel') }}
            </x-secondary-button>
            <x-primary-button>{{ __('Convert Lead') }}</x-primary-button>
        </div>
    </form>
</x-modal>

{{-- Duplicate customer resolution modal --}}
<x-modal name="convert-lead-duplicates" :show="$showDuplicateModal" focusable>
    <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">{{ __('Matching Customers Found') }}</h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ __('A customer with this email or phone already exists. Choose how to continue.') }}
        </p>

        @php
            $hiddenFields = [
                'name' => old('name', $lead->name),
                'email' => old('email', $lead->email),
                'phone' => old('phone', $lead->phone),
                'create_opportunity' => old('create_opportunity', $canCreateOpportunity ? '1' : '0'),
            ];
        @endphp

        <form method="POST" action="{{ route('leads.convert', $lead) }}" class="mt-6 space-y-3">
            @csrf
            @foreach ($hiddenFields as $field => $value)
                <input type="hidden" name="{{ $field }}" value="{{ $value }}">
            @endforeach

            @foreach ($duplicateCustomers as $customer)
                <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-4 cursor-pointer hover:border-indigo-300 hover:bg-indigo-50/30 transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50/50">
                    <input
                        type="radio"
                        name="existing_customer_id"
                        value="{{ $customer['id'] }}"
                        class="mt-1 border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        required
                        @checked((string) old('existing_customer_id') === (string) $customer['id'])
                    />
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-medium text-slate-900">{{ $customer['name'] }}</span>
                        @if ($customer['company'])
                            <span class="block text-sm text-slate-600">{{ $customer['company'] }}</span>
                        @endif
                        <span class="mt-1 block text-xs text-slate-500">
                            {{ $customer['email'] ?: '—' }}
                            @if ($customer['email'] && $customer['phone'])
                                ·
                            @endif
                            {{ $customer['phone'] ?: '' }}
                        </span>
                    </span>
                </label>
            @endforeach

            <x-input-error :messages="$errors->get('existing_customer_id')" class="mt-4" />
            <x-input-error :messages="$errors->get('duplicate_customer')" class="mt-2" />
            <x-input-error :messages="$errors->get('lead')" class="mt-2" />

            <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <x-secondary-button type="button" x-on:click="$dispatch('close-modal', 'convert-lead-duplicates')">
                    {{ __('Cancel') }}
                </x-secondary-button>
                <x-primary-button>{{ __('Use Existing Customer') }}</x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('leads.convert', $lead) }}" class="mt-3 flex justify-end">
            @csrf
            @foreach ($hiddenFields as $field => $value)
                <input type="hidden" name="{{ $field }}" value="{{ $value }}">
            @endforeach
            <input type="hidden" name="force_create" value="1">
            <button
                type="submit"
                class="inline-flex items-center justify-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
            >
                {{ __('Create New Customer Anyway') }}
            </button>
        </form>
    </div>
</x-modal>

@if ($showDuplicateModal)
    <div x-data x-init="$dispatch('open-modal', 'convert-lead-duplicates')"></div>
@endif
