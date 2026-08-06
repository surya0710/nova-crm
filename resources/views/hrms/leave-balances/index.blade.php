@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        __('Employee'),
        __('Type'),
        __('Entitled'),
        __('Used'),
        __('Pending'),
        __('Balance'),
        __('Ledger'),
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Leave Balances')"
        :subtitle="__('Employee leave entitlements for :year', ['year' => $year])"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Leave Balances'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('hrms.leave-balances.ledger')" variant="secondary" size="sm">{{ __('View Ledger') }}</x-ui.button>
        </x-slot:actions>

        @can('create', \App\Models\LeaveApplication::class)
            <div class="grid grid-cols-1 gap-4 mb-6 lg:grid-cols-2">
                <x-ui.card>
                    <x-entity.section :title="__('Allocate Balance')">
                        <form method="POST" action="{{ route('hrms.leave-balances.allocate') }}" class="grid grid-cols-2 gap-3">
                            @csrf
                            <input type="hidden" name="year" value="{{ $year }}" />
                            <x-forms.field :label="__('Employee')" name="employee_id" class="col-span-2">
                                <x-forms.select name="employee_id" required>
                                    <option value="">{{ __('Employee') }}</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                                    @endforeach
                                </x-forms.select>
                            </x-forms.field>
                            <x-forms.field :label="__('Leave Type')" name="leave_type_id">
                                <x-forms.select name="leave_type_id" required>
                                    <option value="">{{ __('Leave Type') }}</option>
                                    @foreach ($leaveTypes as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </x-forms.select>
                            </x-forms.field>
                            <x-forms.field :label="__('Days')" name="days">
                                <x-forms.input name="days" type="number" step="0.5" placeholder="{{ __('Days') }}" required />
                            </x-forms.field>
                            <div class="col-span-2">
                                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Allocate') }}</x-ui.button>
                            </div>
                        </form>
                    </x-entity.section>
                </x-ui.card>
                <x-ui.card>
                    <x-entity.section :title="__('Manual Adjustment')">
                        <form method="POST" action="{{ route('hrms.leave-balances.adjust') }}" class="grid grid-cols-2 gap-3">
                            @csrf
                            <input type="hidden" name="year" value="{{ $year }}" />
                            <x-forms.field :label="__('Employee')" name="employee_id" class="col-span-2">
                                <x-forms.select name="employee_id" required>
                                    <option value="">{{ __('Employee') }}</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                                    @endforeach
                                </x-forms.select>
                            </x-forms.field>
                            <x-forms.field :label="__('Leave Type')" name="leave_type_id">
                                <x-forms.select name="leave_type_id" required>
                                    <option value="">{{ __('Leave Type') }}</option>
                                    @foreach ($leaveTypes as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </x-forms.select>
                            </x-forms.field>
                            <x-forms.field :label="__('Quantity')" name="quantity">
                                <x-forms.input name="quantity" type="number" step="0.5" placeholder="{{ __('+/- Days') }}" required />
                            </x-forms.field>
                            <x-forms.field :label="__('Remarks')" name="remarks" class="col-span-2">
                                <x-forms.input name="remarks" placeholder="{{ __('Remarks') }}" required />
                            </x-forms.field>
                            <div class="col-span-2">
                                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Adjust') }}</x-ui.button>
                            </div>
                        </form>
                    </x-entity.section>
                </x-ui.card>
            </div>
        @endcan

        <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
            @foreach ($balances as $balance)
                <tr class="hover:bg-surface-muted/60 transition">
                    <td class="px-4 py-3 text-sm text-ink-heading">{{ $balance->employee->first_name }} {{ $balance->employee->last_name }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $balance->leaveType->name }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $balance->entitled }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $balance->used }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $balance->pending }}</td>
                    <td class="px-4 py-3 text-sm font-medium text-ink-heading">{{ $balance->balance }}</td>
                    <td class="px-4 py-3">
                        <x-ui.button :href="route('hrms.leave-balances.ledger', ['leave_balance_id' => $balance->id])" variant="ghost" size="sm">{{ __('View') }}</x-ui.button>
                    </td>
                </tr>
            @endforeach
        </x-tables.table>
        <div class="mt-4">{{ $balances->links() }}</div>
    </x-layouts.entity-listing>
</x-app-layout>
