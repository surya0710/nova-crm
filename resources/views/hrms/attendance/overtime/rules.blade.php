@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <x-ui.page-header :title="__('Overtime Rules')">
            <x-slot:actions>
                <x-ui.button :href="route('hrms.attendance.overtime.entries')" variant="secondary" size="sm">{{ __('Approval Queue') }}</x-ui.button>
                <x-ui.button :href="route('hrms.attendance.overtime.rules.create')" size="sm">{{ __('Create rule') }}</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card>
            <form method="GET" action="{{ route('hrms.attendance.overtime.rules') }}" class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-4">
                <x-forms.field :label="__('Search')" name="search">
                    <x-forms.input name="search" :value="request('search')" />
                </x-forms.field>
                <x-forms.field :label="__('Type')" name="rule_type">
                    <x-forms.select name="rule_type">
                        <option value="">{{ __('All types') }}</option>
                        @foreach ($ruleTypes as $value => $label)
                            <option value="{{ $value }}" @selected(request('rule_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Status')" name="is_active">
                    <x-forms.select name="is_active">
                        <option value="">{{ __('All') }}</option>
                        <option value="1" @selected(request('is_active') === '1')>{{ __('Active') }}</option>
                        <option value="0" @selected(request('is_active') === '0')>{{ __('Inactive') }}</option>
                    </x-forms.select>
                </x-forms.field>
                <div class="flex items-end">
                    <x-ui.button type="submit" size="sm">{{ __('Filter') }}</x-ui.button>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-edge text-left text-ink-muted">
                            <th class="px-3 py-2">{{ __('Name') }}</th>
                            <th class="px-3 py-2">{{ __('Type') }}</th>
                            <th class="px-3 py-2">{{ __('Min / Max') }}</th>
                            <th class="px-3 py-2">{{ __('Approval') }}</th>
                            <th class="px-3 py-2">{{ __('Active') }}</th>
                            <th class="px-3 py-2">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rules as $rule)
                            <tr class="border-b border-edge/60">
                                <td class="px-3 py-2">{{ $rule->name }}</td>
                                <td class="px-3 py-2">{{ $rule->ruleTypeLabel() }}</td>
                                <td class="px-3 py-2">{{ $rule->minimum_minutes }} / {{ $rule->maximum_minutes ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $rule->requires_approval ? __('Yes') : __('No') }}</td>
                                <td class="px-3 py-2">{{ $rule->is_active ? __('Yes') : __('No') }}</td>
                                <td class="px-3 py-2 space-x-2">
                                    <x-ui.button :href="route('hrms.attendance.overtime.rules.edit', $rule)" variant="secondary" size="sm">{{ __('Edit') }}</x-ui.button>
                                    @if ($rule->is_active)
                                        <form method="POST" action="{{ route('hrms.attendance.overtime.rules.deactivate', $rule) }}" class="inline">
                                            @csrf
                                            <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Deactivate') }}</x-ui.button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('hrms.attendance.overtime.rules.activate', $rule) }}" class="inline">
                                            @csrf
                                            <x-ui.button type="submit" size="sm">{{ __('Activate') }}</x-ui.button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-6 text-ink-muted">{{ __('No overtime rules configured.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $rules->links() }}</div>
        </x-ui.card>
    </div>
@endsection
