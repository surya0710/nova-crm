<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$asset->name"
        :subtitle="$asset->asset_code"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Assets'), 'href' => route('hrms.assets.index')],
                ['label' => $asset->name, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('hrms.assets.index')" variant="secondary" size="sm">{{ __('Back to Assets') }}</x-ui.button>
        </x-slot:actions>

        <x-slot:tabs>
            <x-ui.badge variant="neutral">{{ config('hrms.asset_statuses.'.$asset->status, $asset->status) }}</x-ui.badge>
        </x-slot:tabs>

        <x-entity.section :title="__('Asset Details')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Category')">{{ config('hrms.asset_categories.'.$asset->category, $asset->category) }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Serial')">{{ $asset->serial_number ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Status')">{{ config('hrms.asset_statuses.'.$asset->status, $asset->status) }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Assigned To')">{{ $asset->employee?->full_name ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Assigned Date')">{{ $asset->assigned_date?->format('M j, Y') ?? '—' }}</x-entity.definition-item>
            </x-entity.definition-list>
            @if ($asset->notes)
                <p class="mt-3 text-sm text-ink-muted">{{ $asset->notes }}</p>
            @endif
        </x-entity.section>

        <x-entity.section :title="__('Assignment History')">
            @forelse ($asset->assignments as $assignment)
                <div class="border-b border-line py-2 text-sm last:border-0">
                    {{ $assignment->employee->full_name }} · {{ $assignment->assigned_date->format('M j, Y') }}
                    @if ($assignment->return_date)
                        → {{ $assignment->return_date->format('M j, Y') }}
                    @else
                        ({{ __('Active') }})
                    @endif
                </div>
            @empty
                <p class="text-sm text-ink-muted">{{ __('No assignment history.') }}</p>
            @endforelse
        </x-entity.section>

        <x-slot:aside>
            @if (in_array($asset->status, ['available', 'returned']))
                <x-entity.section :title="__('Assign Asset')">
                    <form method="POST" action="{{ route('hrms.assets.assign', $asset) }}" class="space-y-3">
                        @csrf
                        <x-forms.field :label="__('Employee')" name="employee_id">
                            <x-forms.select name="employee_id" required>
                                <option value="">{{ __('Select Employee') }}</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                                @endforeach
                            </x-forms.select>
                        </x-forms.field>
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Assign') }}</x-ui.button>
                    </form>
                </x-entity.section>
            @elseif ($asset->status === 'assigned')
                <x-entity.section :title="__('Return Asset')">
                    <form method="POST" action="{{ route('hrms.assets.return', $asset) }}" class="space-y-3">
                        @csrf
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Mark Returned') }}</x-ui.button>
                    </form>
                    <form method="POST" action="{{ route('hrms.assets.mark-lost', $asset) }}" class="mt-4">
                        @csrf
                        <x-ui.button type="submit" variant="ghost" size="sm" class="text-danger">{{ __('Mark as Lost') }}</x-ui.button>
                    </form>
                </x-entity.section>
            @endif
        </x-slot:aside>
    </x-layouts.entity-detail>
</x-app-layout>
