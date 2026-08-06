<x-app-layout>
    <x-flash-messages />

    <x-layouts.workspace-home
        :title="__('Operations')"
        :subtitle="__('Tasks and daily execution')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Operations'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-workspace.quick-actions :actions="$quickActions" />
        </x-slot:actions>

        <x-slot:kpis>
            @forelse ($kpis as $kpi)
                <x-ui.stat-card
                    :label="$kpi['label']"
                    :value="$kpi['value']"
                    :hint="$kpi['hint'] ?? null"
                />
            @empty
                <x-ui.stat-card :label="__('Operations')" :value="__('—')" :hint="__('No metrics available for your role')" />
            @endforelse
        </x-slot:kpis>

        <div class="space-y-6">
            <x-workspace.widget
                :title="__('My tasks')"
                :subtitle="__('Assigned to you')"
                :href="route('tasks.list')"
            >
                @if ($myTasks->isEmpty())
                    <x-ui.empty-state-preset variant="tasks" class="!py-6" />
                @else
                    <ul class="divide-y divide-line -mx-1">
                        @foreach ($myTasks as $task)
                            <li class="px-1 py-2">
                                <a href="{{ route('tasks.show', $task) }}" class="flex items-center justify-between gap-3 text-sm hover:text-primary-600">
                                    <span class="truncate font-medium text-ink-heading">{{ $task->title }}</span>
                                    <span class="shrink-0 text-xs text-ink-muted">
                                        {{ $task->due_date?->format('M j') ?? __('No due date') }}
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-workspace.widget>

            <x-workspace.widget
                :title="__('Overdue tasks')"
                :subtitle="__('Past due date')"
                :href="route('tasks.list')"
            >
                @if ($overdueTasks->isEmpty())
                    <p class="text-sm text-ink-muted">{{ __('No overdue tasks.') }}</p>
                @else
                    <ul class="divide-y divide-line -mx-1">
                        @foreach ($overdueTasks as $task)
                            <li class="px-1 py-2">
                                <a href="{{ route('tasks.show', $task) }}" class="flex items-center justify-between gap-3 text-sm hover:text-primary-600">
                                    <span class="truncate font-medium text-ink-heading">{{ $task->title }}</span>
                                    <span class="shrink-0 text-xs text-danger">{{ $task->due_date?->format('M j') }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-workspace.widget>
        </div>
    </x-layouts.workspace-home>
</x-app-layout>
