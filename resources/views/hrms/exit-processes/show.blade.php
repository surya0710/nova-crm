<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="__('Exit Process')"
        :subtitle="$exitProcess->employee->full_name"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Exit Processes'), 'href' => route('hrms.exit-processes.index')],
                ['label' => $exitProcess->employee->full_name, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('hrms.exit-processes.index')" variant="secondary" size="sm">{{ __('Back to Exit Processes') }}</x-ui.button>
        </x-slot:actions>

        <x-slot:tabs>
            <x-ui.badge variant="neutral">{{ config('hrms.exit_process_statuses.'.$exitProcess->status, $exitProcess->status) }}</x-ui.badge>
        </x-slot:tabs>

        <x-entity.section :title="__('Details')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Exit Type')">{{ config('hrms.exit_types.'.$exitProcess->exit_type) }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Last Working Day')">{{ $exitProcess->last_working_day->format('M j, Y') }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Status')">{{ config('hrms.exit_process_statuses.'.$exitProcess->status) }}</x-entity.definition-item>
            </x-entity.definition-list>
        </x-entity.section>

        <x-slot:aside>
            <x-entity.section :title="__('Checklist')">
                @if (in_array($exitProcess->status, ['in_progress', 'pending_approval']))
                    <form method="POST" action="{{ route('hrms.exit-processes.update', $exitProcess) }}" class="space-y-2 text-sm">
                        @csrf @method('PUT')
                        @foreach (['checklist_assets_returned' => 'Assets Returned', 'checklist_documents_completed' => 'Documents Completed', 'checklist_knowledge_transfer' => 'Knowledge Transfer', 'checklist_manager_approval' => 'Manager Approval', 'checklist_hr_approval' => 'HR Approval'] as $field => $label)
                            <label class="flex items-center gap-2 text-ink-heading">
                                <input type="checkbox" name="{{ $field }}" value="1" @checked($exitProcess->$field) class="rounded border-line" />
                                {{ __($label) }}
                            </label>
                        @endforeach
                        <x-ui.button type="submit" variant="primary" size="sm" class="mt-3">{{ __('Update Checklist') }}</x-ui.button>
                    </form>
                @else
                    <ul class="space-y-1 text-sm">
                        @foreach (['checklist_assets_returned' => 'Assets Returned', 'checklist_documents_completed' => 'Documents Completed', 'checklist_knowledge_transfer' => 'Knowledge Transfer', 'checklist_manager_approval' => 'Manager Approval', 'checklist_hr_approval' => 'HR Approval'] as $field => $label)
                            <li class="text-ink-heading">{{ $exitProcess->$field ? '✓' : '✗' }} {{ __($label) }}</li>
                        @endforeach
                    </ul>
                @endif
            </x-entity.section>

            @if (in_array($exitProcess->status, ['in_progress', 'pending_approval']))
                <div class="flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('hrms.exit-processes.complete', $exitProcess) }}">
                        @csrf
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Complete Exit') }}</x-ui.button>
                    </form>
                    <form method="POST" action="{{ route('hrms.exit-processes.cancel', $exitProcess) }}">
                        @csrf
                        <x-ui.button type="submit" variant="danger" size="sm">{{ __('Cancel Exit') }}</x-ui.button>
                    </form>
                </div>
            @endif
        </x-slot:aside>
    </x-layouts.entity-detail>
</x-app-layout>
