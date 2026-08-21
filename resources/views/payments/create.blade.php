<x-app-layout>
    <x-flash-messages />

    <x-layouts.create
        :title="__('Record :label', ['label' => crm_term('payment')])"
        max-width="2xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('payments'), 'href' => route('payments.index')],
                ['label' => __('Record :label', ['label' => crm_term('payment')]), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('payments.store') }}">
            @csrf
            <div class="space-y-6">
                <x-forms.section :title="__('Payment')">
                    <div class="sm:col-span-2">
                        <x-forms.field :label="crm_term('invoice')" name="invoice_id" required>
                            <x-forms.select id="invoice_id" name="invoice_id" required>
                                <option value="">{{ __('Select invoice') }}</option>
                                @foreach ($openInvoices as $openInvoice)
                                    <option value="{{ $openInvoice->id }}" @selected((string) old('invoice_id', $payment->invoice_id) === (string) $openInvoice->id)>
                                        {{ $openInvoice->number }} · {{ $openInvoice->customer->display_name }} · {{ __('Balance') }} {{ number_format($openInvoice->balance_due, 2) }} {{ $openInvoice->currency }}
                                    </option>
                                @endforeach
                            </x-forms.select>
                        </x-forms.field>
                    </div>
                    <x-forms.field :label="__('Amount')" name="amount" required>
                        <x-forms.input id="amount" name="amount" type="number" step="0.01" min="0.01" :value="old('amount', $payment->amount)" required />
                    </x-forms.field>
                    <x-forms.field :label="__('Payment Date')" name="payment_date" required>
                        <x-forms.input id="payment_date" name="payment_date" type="date" :value="old('payment_date', $payment->payment_date?->format('Y-m-d'))" required />
                    </x-forms.field>
                    <x-forms.field :label="__('Payment Method')" name="method" required>
                        <x-forms.select id="method" name="method" required>
                            @foreach (config('payments.methods') as $value => $label)
                                <option value="{{ $value }}" @selected(old('method', $payment->method) === $value)>{{ $label }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Reference / Transaction ID')" name="reference">
                        <x-forms.input id="reference" name="reference" :value="old('reference')" />
                    </x-forms.field>
                    <x-forms.field :label="__('Bank name')" name="bank_name">
                        <x-forms.input id="bank_name" name="bank_name" :value="old('bank_name')" />
                    </x-forms.field>
                    <x-forms.field :label="__('Account name')" name="bank_account_name">
                        <x-forms.input id="bank_account_name" name="bank_account_name" :value="old('bank_account_name')" />
                    </x-forms.field>
                    <x-forms.field :label="__('Account number')" name="bank_account_number">
                        <x-forms.input id="bank_account_number" name="bank_account_number" :value="old('bank_account_number')" />
                    </x-forms.field>
                    <x-forms.field :label="__('IFSC / routing')" name="bank_ifsc">
                        <x-forms.input id="bank_ifsc" name="bank_ifsc" :value="old('bank_ifsc')" />
                    </x-forms.field>
                    <div class="sm:col-span-2">
                        <x-forms.field :label="__('Notes')" name="notes">
                            <x-forms.textarea id="notes" name="notes" rows="2">{{ old('notes') }}</x-forms.textarea>
                        </x-forms.field>
                    </div>
                </x-forms.section>
            </div>
            <x-forms.footer :cancel-href="route('payments.index')" :submit-label="__('Record :label', ['label' => crm_term('payment')])" />
        </form>
    </x-layouts.create>
</x-app-layout>
