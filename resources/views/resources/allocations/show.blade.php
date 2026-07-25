<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail :title="__('Details Allocations')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Details Allocations'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="max-w-4xl space-y-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-6 sm:p-8">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div>
                    <dt class="text-slate-500">{{ __('Employee') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">
                        @if ($allocation->employee)
                            <a href="{{ route('resources.employees.workload', $allocation->employee) }}" class="text-primary-600 hover:text-primary-700">{{ $allocation->employee->full_name }}</a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Type') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $allocation->allocation_type_label }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Project') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">
                        @if ($allocation->project)
                            <a href="{{ route('projects.show', $allocation->project) }}" class="text-primary-600 hover:text-primary-700">{{ $allocation->project->name }}</a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Task') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">
                        @if ($allocation->task)
                            <a href="{{ route('tasks.show', $allocation->task) }}" class="text-primary-600 hover:text-primary-700">{{ $allocation->task->title }}</a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Allocation') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $allocation->allocation_percentage }}%</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Planned hours') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $allocation->planned_hours ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('Start') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $allocation->planned_start_date->toDateString() }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">{{ __('End') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $allocation->planned_end_date->toDateString() }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-slate-500">{{ __('Notes') }}</dt>
                    <dd class="mt-1 text-slate-800 whitespace-pre-wrap">{{ $allocation->notes ?: '—' }}</dd>
                </div>
            </dl>
        </div>

        @if (isset($metadataFields) && isset($metadataPresenter))
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-6 sm:p-8">
                @include('metadata-fields._runtime_detail', [
                    'metadataFields' => $metadataFields ?? collect(),
                    'metadataPresenter' => $metadataPresenter,
                    'record' => $allocation,
                ])
            </div>
        @endif
    </div>
    </x-layouts.entity-detail>
</x-app-layout>
