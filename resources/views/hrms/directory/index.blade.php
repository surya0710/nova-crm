<x-app-layout>
    <x-layouts.entity-listing :title="__('Employee Directory')" :subtitle="__('Browse and search employees across your organization')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Directory'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:filters>
            <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-5">
                <x-forms.field :label="__('Search')" name="q">
                    <x-forms.input name="q" :value="request('q')" placeholder="{{ __('Search name or code') }}" />
                </x-forms.field>
                <x-forms.field :label="__('Department')" name="department_id">
                    <x-forms.select name="department_id">
                        <option value="">{{ __('All Departments') }}</option>
                        @foreach ($filters['departments'] as $dept)
                            <option value="{{ $dept->id }}" @selected(request('department_id') == $dept->id)>{{ $dept->name }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Designation')" name="designation_id">
                    <x-forms.select name="designation_id">
                        <option value="">{{ __('All Designations') }}</option>
                        @foreach ($filters['designations'] as $des)
                            <option value="{{ $des->id }}" @selected(request('designation_id') == $des->id)>{{ $des->name }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Branch')" name="branch_id">
                    <x-forms.select name="branch_id">
                        <option value="">{{ __('All Branches') }}</option>
                        @foreach ($filters['branches'] as $branch)
                            <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <div class="flex items-end">
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Search') }}</x-ui.button>
                </div>
            </form>
        </x-slot:filters>

        @if ($employees->isEmpty())
            <x-ui.card>
                @if (request()->hasAny(['q', 'department_id', 'designation_id', 'branch_id']))
                    <x-ui.empty-state-preset variant="search" />
                @else
                    <x-ui.empty-state-preset variant="employees" />
                @endif
            </x-ui.card>
        @else
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($employees as $employee)
                    <a href="{{ route('hrms.directory.show', $employee) }}" class="rounded-xl border border-line bg-surface-card p-4 shadow-sm transition hover:border-primary-300">
                        <p class="font-medium text-ink-heading">{{ $employee->full_name }}</p>
                        <p class="text-sm text-ink-muted">{{ $employee->designation?->name ?? '—' }}</p>
                        <p class="text-sm text-ink-muted">{{ $employee->department?->name ?? '—' }}</p>
                    </a>
                @endforeach
            </div>
        @endif

        @if ($employees->hasPages())
            <x-slot:pagination>{{ $employees->withQueryString()->links() }}</x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
