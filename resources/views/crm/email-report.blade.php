<x-app-layout>
    <x-layouts.entity-listing
        :title="__('CRM email report')"
        :subtitle="__('Delivery volume and rates for this organization')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => __('Reports'), 'href' => route('crm.reports')],
                ['label' => __('Email'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="get" class="mb-6 flex flex-wrap items-end gap-3">
            <x-forms.field :label="__('From')" name="from">
                <x-forms.input type="date" name="from" :value="$from" />
            </x-forms.field>
            <x-forms.field :label="__('To')" name="to">
                <x-forms.input type="date" name="to" :value="$to" />
            </x-forms.field>
            <x-ui.button type="submit" size="sm">{{ __('Apply') }}</x-ui.button>
        </form>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                'emails_queued' => __('Queued'),
                'emails_sent' => __('Sent'),
                'emails_delivered' => __('Delivered'),
                'emails_failed' => __('Failed'),
                'emails_bounced' => __('Bounced'),
                'delivery_rate' => __('Delivery rate'),
                'failure_rate' => __('Failure rate'),
            ] as $key => $label)
                <x-ui.card>
                    <p class="text-sm text-ink-muted">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-semibold text-ink-heading">
                        {{ in_array($key, ['delivery_rate', 'failure_rate'], true) ? $metrics[$key].'%' : $metrics[$key] }}
                    </p>
                </x-ui.card>
            @endforeach
        </div>
    </x-layouts.entity-listing>
</x-app-layout>
