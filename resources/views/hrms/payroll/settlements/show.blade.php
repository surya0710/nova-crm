<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="__('Settlement')"
        :subtitle="$settlement->settlement_number"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Settlements'), 'href' => route('hrms.payroll.settlements.index')],
                ['label' => $settlement->settlement_number, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('hrms.payroll.settlements.index')" variant="secondary" size="sm">{{ __('Back') }}</x-ui.button>
        </x-slot:actions>

        <x-slot:tabs>
            <x-ui.badge variant="neutral">{{ $settlement->status }}</x-ui.badge>
        </x-slot:tabs>

        <x-entity.section :title="__('Summary')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Employee')">{{ $settlement->employee?->full_name }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Status')">{{ $settlement->status }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Net settlement')">{{ number_format((float) $settlement->net_settlement, 2) }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Completed')">{{ $settlement->completed_at?->toDateTimeString() }}</x-entity.definition-item>
            </x-entity.definition-list>
        </x-entity.section>

        <x-entity.section :title="__('Settlement Statement')">
            <div class="space-y-0 text-sm">
                @foreach ([
                    ['label' => __('Pending salary'), 'value' => $settlement->pending_salary],
                    ['label' => __('Leave encashment'), 'value' => $settlement->leave_encashment],
                    ['label' => __('Reimbursements'), 'value' => $settlement->reimbursements],
                ] as $row)
                    <div class="flex justify-between border-b border-line py-3">
                        <span class="text-ink-muted">{{ $row['label'] }}</span>
                        <span class="text-ink-heading">{{ number_format((float) $row['value'], 2) }}</span>
                    </div>
                @endforeach
                @foreach ([
                    ['label' => __('Loan recovery'), 'value' => $settlement->loan_recovery],
                    ['label' => __('Advance recovery'), 'value' => $settlement->advance_recovery],
                    ['label' => __('Asset deductions'), 'value' => $settlement->asset_deductions],
                    ['label' => __('Statutory deductions'), 'value' => $settlement->statutory_deductions],
                ] as $row)
                    <div class="flex justify-between border-b border-line py-3">
                        <span class="text-ink-muted">{{ $row['label'] }}</span>
                        <span class="text-danger">−{{ number_format((float) $row['value'], 2) }}</span>
                    </div>
                @endforeach
                <div class="flex justify-between bg-surface-muted/50 py-3 font-medium">
                    <span class="text-ink-heading">{{ __('Net settlement') }}</span>
                    <span class="text-ink-heading">{{ number_format((float) $settlement->net_settlement, 2) }}</span>
                </div>
            </div>
        </x-entity.section>

        @if ($settlement->notes)
            <x-entity.section :title="__('Notes')">
                <p class="text-sm text-ink-muted">{{ $settlement->notes }}</p>
            </x-entity.section>
        @endif
    </x-layouts.entity-detail>
</x-app-layout>
