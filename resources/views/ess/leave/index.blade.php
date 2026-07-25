@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        __('Type'),
        __('Dates'),
        __('Days'),
        __('Status'),
        '',
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('My Leave')"
        :subtitle="__('Apply for leave and track your balances')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('My HR'), 'href' => route('ess.dashboard')],
                ['label' => __('Leave'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @include('ess.partials.nav')

        <x-ui.card class="mb-6">
            <x-entity.section :title="__('Apply leave')">
                <form method="POST" action="{{ route('ess.leave.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                    @csrf
                    <x-forms.field :label="__('Leave Type')" name="leave_type_id">
                        <x-forms.select name="leave_type_id" required>
                            <option value="">{{ __('Leave Type') }}</option>
                            @foreach ($leaveTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Start date')" name="start_date">
                        <x-forms.input name="start_date" type="date" required />
                    </x-forms.field>
                    <x-forms.field :label="__('End date')" name="end_date">
                        <x-forms.input name="end_date" type="date" required />
                    </x-forms.field>
                    <x-forms.field :label="__('Reason')" name="reason">
                        <x-forms.input name="reason" placeholder="{{ __('Reason') }}" />
                    </x-forms.field>
                    <x-forms.checkbox name="is_half_day" value="1" :label="__('Half Day')" />
                    <x-forms.field :label="__('Half Day Period')" name="half_day_period">
                        <x-forms.select name="half_day_period">
                            <option value="">{{ __('Half Day Period') }}</option>
                            @foreach (config('hrms.half_day_periods', []) as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <div class="md:col-span-4">
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Apply Leave') }}</x-ui.button>
                    </div>
                </form>
            </x-entity.section>
        </x-ui.card>

        <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-2">
            <x-ui.card>
                <x-entity.section :title="__('Balances')">
                    @foreach ($balances as $balance)
                        <div class="flex justify-between text-sm py-2 border-b border-line last:border-0">
                            <span class="text-ink-heading">{{ $balance->leaveType->name }}</span>
                            <span class="font-medium text-ink-heading">{{ $balance->balance }}</span>
                        </div>
                    @endforeach
                </x-entity.section>
            </x-ui.card>
            <x-ui.card>
                <x-entity.section :title="__('Recent Ledger')">
                    @forelse ($transactions as $tx)
                        <div class="text-sm py-2 border-b border-line last:border-0 text-ink-muted">
                            {{ $tx->leaveBalance->leaveType->name }} · {{ $tx->transaction_type }} · {{ $tx->quantity }}
                        </div>
                    @empty
                        <p class="text-sm text-ink-muted">{{ __('No transactions.') }}</p>
                    @endforelse
                </x-entity.section>
            </x-ui.card>
        </div>

        <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
            @foreach ($applications as $application)
                <tr class="hover:bg-surface-muted/60 transition">
                    <td class="px-4 py-3 text-sm text-ink-heading">{{ $application->leaveType->name }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $application->start_date->format('M j') }}–{{ $application->end_date->format('M j, Y') }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $application->days }}</td>
                    <td class="px-4 py-3">
                        <x-ui.badge variant="neutral">{{ $statuses[$application->status] ?? $application->status }}</x-ui.badge>
                    </td>
                    <td class="px-4 py-3">
                        @if (in_array($application->status, ['draft', 'pending']))
                            <form method="POST" action="{{ route('ess.leave.destroy', $application) }}">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="ghost" size="sm">{{ __('Withdraw') }}</x-ui.button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-tables.table>
        <div class="mt-4">{{ $applications->links() }}</div>
    </x-layouts.entity-listing>
</x-app-layout>
