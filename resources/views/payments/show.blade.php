<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$payment->number"
        :subtitle="$payment->formatted_amount.' · '.$payment->payment_date->format('M j, Y')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('payments'), 'href' => route('payments.index')],
                ['label' => $payment->number, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-entity.section :title="__('Payment details')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Amount')">
                    <span class="font-semibold text-ink-heading">{{ $payment->formatted_amount }}</span>
                </x-entity.definition-item>
                <x-entity.definition-item :label="__('Method')">{{ $payment->method_label }}</x-entity.definition-item>
                @if ($payment->reference)
                    <x-entity.definition-item :label="__('Reference')" :span="2">{{ $payment->reference }}</x-entity.definition-item>
                @endif
                <x-entity.definition-item :label="crm_term('invoice')" :span="2">
                    <a href="{{ route('invoices.show', $payment->invoice) }}" class="text-primary-600 hover:text-primary-700">{{ $payment->invoice->number }}</a>
                </x-entity.definition-item>
                <x-entity.definition-item :label="crm_term('customer')" :span="2">
                    <a href="{{ route('customers.show', $payment->customer) }}" class="text-primary-600 hover:text-primary-700">{{ $payment->customer->display_name }}</a>
                </x-entity.definition-item>
                <x-entity.definition-item :label="__('Recorded By')">{{ $payment->recorder?->name ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Payment Date')">{{ $payment->payment_date->format('M j, Y') }}</x-entity.definition-item>
                @if ($payment->notes)
                    <x-entity.definition-item :label="__('Notes')" :span="2">
                        <div class="whitespace-pre-line text-ink">{{ $payment->notes }}</div>
                    </x-entity.definition-item>
                @endif
            </x-entity.definition-list>
        </x-entity.section>

        @can('create', App\Models\Payment::class)
            <x-client-email-form
                :action="route('payments.send', $payment)"
                :email="old('email', $payment->customer->email)"
                :submit-label="__('Send Receipt')"
                :title="__('Email Receipt')"
                :description="__('Send payment confirmation to your customer')"
                :organization="$organization"
                :missing-email-hint="! $payment->customer->email"
            />
        @endcan

        <x-slot:aside>
            <x-ui.button :href="route('payments.index')" variant="link" size="sm">← {{ __('Back to :label', ['label' => strtolower(crm_term('payments'))]) }}</x-ui.button>
        </x-slot:aside>
    </x-layouts.entity-detail>
</x-app-layout>
