@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <x-ui.page-header :title="__('Overtime Approval Queue')">
            <x-slot:actions>
                <x-ui.button :href="route('hrms.attendance.overtime.rules')" variant="secondary" size="sm">{{ __('Rules') }}</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card>
            <form method="GET" action="{{ route('hrms.attendance.overtime.entries') }}" class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-4">
                <x-forms.field :label="__('Search')" name="search">
                    <x-forms.input name="search" :value="request('search')" />
                </x-forms.field>
                <x-forms.field :label="__('Status')" name="status">
                    <x-forms.select name="status">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Rule')" name="rule_id">
                    <x-forms.select name="rule_id">
                        <option value="">{{ __('All rules') }}</option>
                        @foreach ($rules as $rule)
                            <option value="{{ $rule->id }}" @selected((string) request('rule_id') === (string) $rule->id)>{{ $rule->name }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Type')" name="rule_type">
                    <x-forms.select name="rule_type">
                        <option value="">{{ __('All types') }}</option>
                        @foreach ($ruleTypes as $value => $label)
                            <option value="{{ $value }}" @selected(request('rule_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('From')" name="date_from">
                    <x-forms.input type="date" name="date_from" :value="request('date_from')" />
                </x-forms.field>
                <x-forms.field :label="__('To')" name="date_to">
                    <x-forms.input type="date" name="date_to" :value="request('date_to')" />
                </x-forms.field>
                <x-forms.field :label="__('Employee ID')" name="employee_id">
                    <x-forms.input type="number" name="employee_id" :value="request('employee_id')" />
                </x-forms.field>
                <x-forms.field :label="__('Branch ID')" name="branch_id">
                    <x-forms.input type="number" name="branch_id" :value="request('branch_id')" />
                </x-forms.field>
                <x-forms.field :label="__('Department ID')" name="department_id">
                    <x-forms.input type="number" name="department_id" :value="request('department_id')" />
                </x-forms.field>
                <div class="flex items-end md:col-span-4">
                    <x-ui.button type="submit" size="sm">{{ __('Filter') }}</x-ui.button>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-edge text-left text-ink-muted">
                            <th class="px-3 py-2">{{ __('Employee') }}</th>
                            <th class="px-3 py-2">{{ __('Date') }}</th>
                            <th class="px-3 py-2">{{ __('Type') }}</th>
                            <th class="px-3 py-2">{{ __('Minutes') }}</th>
                            <th class="px-3 py-2">{{ __('Status') }}</th>
                            <th class="px-3 py-2">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            <tr class="border-b border-edge/60">
                                <td class="px-3 py-2">{{ $entry->employee?->full_name }}</td>
                                <td class="px-3 py-2">{{ $entry->attendance_date->toDateString() }}</td>
                                <td class="px-3 py-2">{{ $entry->rule_type }}</td>
                                <td class="px-3 py-2">{{ $entry->minutes }}</td>
                                <td class="px-3 py-2">{{ $entry->statusLabel() }}</td>
                                <td class="px-3 py-2 space-x-2">
                                    @if ($entry->isPending())
                                        <form method="POST" action="{{ route('hrms.attendance.overtime.entries.approve', $entry) }}" class="inline">
                                            @csrf
                                            <x-ui.button type="submit" size="sm">{{ __('Approve') }}</x-ui.button>
                                        </form>
                                        <form method="POST" action="{{ route('hrms.attendance.overtime.entries.reject', $entry) }}" class="inline">
                                            @csrf
                                            <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Reject') }}</x-ui.button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-6 text-ink-muted">{{ __('No overtime entries.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $entries->links() }}</div>
        </x-ui.card>
    </div>
@endsection
