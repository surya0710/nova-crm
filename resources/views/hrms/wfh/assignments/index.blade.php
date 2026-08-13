@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        __('Employee'),
        __('Type'),
        __('Weekdays'),
        __('Effective'),
        __('Status'),
        __('Reason'),
        __('Actions'),
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('WFH Assignments')"
        :subtitle="__('Permanent and selected-day work-from-home assignments')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('WFH'), 'href' => route('hrms.wfh.requests.index')],
                ['label' => __('Assignments'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @unless($orgPolicy['enabled'])
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                {{ __('Organization WFH policy is currently disabled. Enable it under Organization Settings → WFH Policies.') }}
            </div>
        @endunless

        @can('create', \App\Models\EmployeeWfhAssignment::class)
            <x-ui.card class="mb-6">
                <x-entity.section :title="__('Add assignment')">
                    <form method="POST" action="{{ route('hrms.wfh.assignments.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        @csrf
                        <x-forms.field :label="__('Employee')" name="employee_id">
                            <x-forms.select name="employee_id" required>
                                <option value="">{{ __('Select employee') }}</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->full_name ?? trim($employee->first_name.' '.$employee->last_name) }}</option>
                                @endforeach
                            </x-forms.select>
                        </x-forms.field>
                        <x-forms.field :label="__('Policy type')" name="policy_type">
                            <x-forms.select name="policy_type" required>
                                @foreach ($policyTypes as $value => $label)
                                    <option value="{{ $value }}">{{ __($label) }}</option>
                                @endforeach
                            </x-forms.select>
                        </x-forms.field>
                        <x-forms.field :label="__('Effective from')" name="effective_from">
                            <x-forms.input name="effective_from" type="date" required />
                        </x-forms.field>
                        <x-forms.field :label="__('Effective to')" name="effective_to">
                            <x-forms.input name="effective_to" type="date" />
                        </x-forms.field>
                        <div class="md:col-span-2">
                            <p class="mb-1 text-sm text-ink-muted">{{ __('Weekdays (selected days only)') }}</p>
                            <div class="flex flex-wrap gap-3">
                                @foreach ($weekdays as $value => $label)
                                    <label class="flex items-center gap-1 text-sm">
                                        <input type="checkbox" name="weekdays[]" value="{{ $value }}" class="rounded border-slate-300">
                                        {{ __($label) }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <x-forms.field :label="__('Reason')" name="reason">
                            <x-forms.input name="reason" />
                        </x-forms.field>
                        <x-forms.checkbox name="is_active" value="1" checked :label="__('Active')" />
                        <div class="flex items-end">
                            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Assign WFH') }}</x-ui.button>
                        </div>
                    </form>
                </x-entity.section>
            </x-ui.card>
        @endcan

        <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
            @forelse ($assignments as $assignment)
                <tr class="hover:bg-surface-muted/60 transition">
                    <td class="px-4 py-3 text-sm font-medium text-ink-heading">
                        {{ $assignment->employee?->full_name ?? trim(($assignment->employee?->first_name ?? '').' '.($assignment->employee?->last_name ?? '')) }}
                    </td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ __(config('hrms.wfh_policy_types.'.$assignment->policy_type, $assignment->policy_type)) }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">
                        @if ($assignment->policy_type === 'selected_days')
                            {{ collect($assignment->weekdays ?? [])->map(fn ($d) => __(config('hrms.wfh_weekdays.'.$d, $d)))->join(', ') }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-ink-muted">
                        {{ $assignment->effective_from?->format('M j, Y') }}
                        →
                        {{ $assignment->effective_to?->format('M j, Y') ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $assignment->is_active ? __('Active') : __('Inactive') }}</td>
                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $assignment->reason ?: '—' }}</td>
                    <td class="px-4 py-3 text-sm">
                        @can('delete', $assignment)
                            <form method="POST" action="{{ route('hrms.wfh.assignments.destroy', $assignment) }}" onsubmit="return confirm(@json(__('End this WFH assignment?')))">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="secondary" size="sm">{{ __('End') }}</x-ui.button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="px-4 py-8 text-center text-sm text-ink-muted">{{ __('No WFH assignments yet.') }}</td>
                </tr>
            @endforelse
        </x-tables.table>

        <div class="mt-4">{{ $assignments->links() }}</div>
    </x-layouts.entity-listing>
</x-app-layout>
