<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Automation')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Automation'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-semibold text-slate-900">{{ __('How automation works') }}</h3>
        </div>
        <div class="p-6 space-y-3 text-sm text-slate-700">
            <p>{{ __('Project and task automations are configured as workflows. When an event fires (for example a task is completed or a comment is added), matching workflows run their actions.') }}</p>
            <p>{{ __('Use the workflow builder to create rules for notifications, status changes, assignments, and more. This page lists the project-related triggers available to those workflows.') }}</p>
            @if ($workflowsUrl)
                <p>
                    <a href="{{ $workflowsUrl }}" class="font-medium text-primary-600 hover:text-primary-700">{{ __('Go to Workflows') }} →</a>
                </p>
            @endif
        </div>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-semibold text-slate-900">{{ __('Available triggers') }}</h3>
        </div>
        @if ($triggers->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No project automation triggers are configured.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Trigger') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Key') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Description') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($triggers as $trigger)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $trigger['label'] ?? $trigger['key'] }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600 font-mono text-xs">{{ $trigger['key'] ?? '' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $trigger['description'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
