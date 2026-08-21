<x-app-layout>
    <x-flash-messages />
    <x-layouts.settings :title="__('Commercial automation')" :subtitle="__('Invoice, payment, quotation, and sales order reminders')">
        <x-slot:breadcrumbs>
            <x-nav.configuration-breadcrumbs :current="__('Commercial automation')" />
        </x-slot:breadcrumbs>
        <form method="POST" action="{{ route('organization.settings.commercial-automation.update') }}" class="max-w-2xl space-y-6">
            @csrf
            @method('PUT')
            <x-entity.section :title="__('Invoices & payments')">
                <div class="space-y-3">
                    <x-forms.checkbox name="invoice_due_reminders" value="1" :label="__('Invoice due reminders')" @checked($settings['invoice_due_reminders'] ?? true) />
                    <x-forms.field :label="__('Days before due date')" name="invoice_due_days_before">
                        <x-forms.input type="number" min="0" name="invoice_due_days_before" :value="old('invoice_due_days_before', $settings['invoice_due_days_before'] ?? 3)" />
                    </x-forms.field>
                    <x-forms.checkbox name="invoice_overdue_reminders" value="1" :label="__('Overdue invoice reminders')" @checked($settings['invoice_overdue_reminders'] ?? true) />
                    <x-forms.checkbox name="payment_confirmation" value="1" :label="__('Payment confirmation to customer')" @checked($settings['payment_confirmation'] ?? true) />
                    <x-forms.checkbox name="payment_receipt" value="1" :label="__('Payment receipt notification to staff')" @checked($settings['payment_receipt'] ?? true) />
                </div>
            </x-entity.section>
            <x-entity.section :title="__('Quotations & sales orders')">
                <div class="space-y-3">
                    <x-forms.checkbox name="quotation_expiry_reminders" value="1" :label="__('Quote expiry reminders')" @checked($settings['quotation_expiry_reminders'] ?? true) />
                    <x-forms.field :label="__('Days before expiry')" name="quotation_expiry_days_before">
                        <x-forms.input type="number" min="0" name="quotation_expiry_days_before" :value="old('quotation_expiry_days_before', $settings['quotation_expiry_days_before'] ?? 2)" />
                    </x-forms.field>
                    <x-forms.checkbox name="sales_order_notifications" value="1" :label="__('Sales order status notifications')" @checked($settings['sales_order_notifications'] ?? true) />
                </div>
            </x-entity.section>
            <x-entity.section :title="__('Customer portal payments')">
                <x-forms.field :label="__('Payment gateway')" name="payment_gateway" :hint="__('Test gateway records the outstanding balance immediately.')">
                    <x-forms.select name="payment_gateway">
                        @foreach ($gateways as $value => $label)
                            <option value="{{ $value }}" @selected(old('payment_gateway', $settings['payment_gateway'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
            </x-entity.section>
            <x-ui.button type="submit" variant="primary">{{ __('Save') }}</x-ui.button>
        </form>
    </x-layouts.settings>
</x-app-layout>
