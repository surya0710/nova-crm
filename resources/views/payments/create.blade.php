<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">{{ __('Record :label', ['label' => crm_term('payment')]) }}</h1>
        </div>
    </x-slot>
    <x-flash-messages />
    <form method="POST" action="{{ route('payments.store') }}" class="max-w-2xl">
        @csrf
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 space-y-5">
                <div>
                    <x-input-label for="invoice_id" :value="crm_term('invoice')" />
                    <select id="invoice_id" name="invoice_id" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">{{ __('Select invoice') }}</option>
                        @foreach ($openInvoices as $openInvoice)
                            <option value="{{ $openInvoice->id }}" @selected((string) old('invoice_id', $payment->invoice_id) === (string) $openInvoice->id)>
                                {{ $openInvoice->number }} · {{ $openInvoice->customer->display_name }} · {{ __('Balance') }} {{ number_format($openInvoice->balance_due, 2) }} {{ $openInvoice->currency }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('invoice_id')" class="mt-2" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <x-input-label for="amount" :value="__('Amount')" />
                        <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="block mt-1 w-full" :value="old('amount', $payment->amount)" required />
                        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="payment_date" :value="__('Payment Date')" />
                        <x-text-input id="payment_date" name="payment_date" type="date" class="block mt-1 w-full" :value="old('payment_date', $payment->payment_date?->format('Y-m-d'))" required />
                        <x-input-error :messages="$errors->get('payment_date')" class="mt-2" />
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <x-input-label for="method" :value="__('Payment Method')" />
                        <select id="method" name="method" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                            @foreach (config('payments.methods') as $value => $label)
                                <option value="{{ $value }}" @selected(old('method', $payment->method) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('method')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="reference" :value="__('Reference / Transaction ID')" />
                        <x-text-input id="reference" name="reference" class="block mt-1 w-full" :value="old('reference')" />
                        <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                    </div>
                </div>
                <div>
                    <x-input-label for="notes" :value="__('Notes')" />
                    <textarea id="notes" name="notes" rows="2" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm">{{ old('notes') }}</textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t bg-slate-50/50 flex justify-between">
                <a href="{{ route('payments.index') }}" class="text-sm text-slate-600">{{ __('Cancel') }}</a>
                <x-primary-button>{{ __('Record :label', ['label' => crm_term('payment')]) }}</x-primary-button>
            </div>
        </div>
    </form>
</x-app-layout>
