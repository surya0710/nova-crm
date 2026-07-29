<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Time logs')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Time logs'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

    @php
        $activeTimer = $task->timeLogs
            ->where('user_id', auth()->id())
            ->first(fn ($log) => $log->end_time === null && in_array($log->source, ['timer', 'paused'], true));
        $estimated = (float) ($task->estimated_hours ?? 0);
        $actual = (float) ($task->actual_hours ?? 0);
        $remaining = max(0, round($estimated - $actual, 2));
    @endphp

    <div class="max-w-2xl space-y-4">
        <div class="grid grid-cols-3 gap-3">
            <div class="rounded-xl border border-line bg-surface-card p-3">
                <p class="text-xs text-ink-muted">{{ __('Estimated') }}</p>
                <p class="mt-1 text-lg font-semibold text-ink-heading">{{ $estimated }}h</p>
            </div>
            <div class="rounded-xl border border-line bg-surface-card p-3">
                <p class="text-xs text-ink-muted">{{ __('Actual') }}</p>
                <p class="mt-1 text-lg font-semibold text-ink-heading">{{ $actual }}h</p>
            </div>
            <div class="rounded-xl border border-line bg-surface-card p-3">
                <p class="text-xs text-ink-muted">{{ __('Remaining') }}</p>
                <p class="mt-1 text-lg font-semibold text-ink-heading">{{ $remaining }}h</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            @if (! $activeTimer)
                <form method="POST" action="{{ route('tasks.time-logs.start', $task) }}">@csrf <x-primary-button>{{ __('Start timer') }}</x-primary-button></form>
            @elseif ($activeTimer->source === 'timer')
                <form method="POST" action="{{ route('tasks.time-logs.pause', $task) }}">@csrf <button class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700">{{ __('Pause') }}</button></form>
                <form method="POST" action="{{ route('tasks.time-logs.stop', $task) }}">@csrf <button class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700">{{ __('Stop') }}</button></form>
            @else
                <form method="POST" action="{{ route('tasks.time-logs.resume', $task) }}">@csrf <x-primary-button>{{ __('Resume') }}</x-primary-button></form>
                <form method="POST" action="{{ route('tasks.time-logs.stop', $task) }}">@csrf <button class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700">{{ __('Stop') }}</button></form>
            @endif
        </div>
        <ul class="rounded-xl bg-white border border-slate-200 divide-y divide-slate-200">
            @forelse ($timeLogs as $log)
                <li class="px-4 py-3 text-sm flex justify-between gap-3">
                    <div>
                        <div class="font-medium text-slate-800">{{ $log->user?->name }} · {{ $log->duration_minutes }} {{ __('min') }} · {{ $log->source_label }}</div>
                        <div class="text-xs text-slate-500">{{ $log->start_time?->toDayDateTimeString() }} — {{ $log->end_time?->toDayDateTimeString() ?? ($log->isPaused() ? __('paused') : __('running')) }}</div>
                    </div>
                    @can('delete', $log)
                        <form method="POST" action="{{ route('tasks.time-logs.destroy', [$task, $log]) }}">@csrf @method('DELETE')<button class="text-red-600 text-xs">{{ __('Delete') }}</button></form>
                    @endcan
                </li>
            @empty
                <li class="px-4 py-8 text-center text-slate-500">{{ __('No time logged yet.') }}</li>
            @endforelse
        </ul>
        <form method="POST" action="{{ route('tasks.time-logs.store', $task) }}" class="rounded-xl bg-white border border-slate-200 p-4 space-y-3">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <x-input-label for="start_time" :value="__('Start')" />
                    <x-text-input id="start_time" type="datetime-local" name="start_time" class="mt-1 w-full" required />
                </div>
                <div>
                    <x-input-label for="end_time" :value="__('End')" />
                    <x-text-input id="end_time" type="datetime-local" name="end_time" class="mt-1 w-full" />
                </div>
            </div>
            <x-text-input name="description" class="w-full" placeholder="{{ __('Description') }}" />
            <x-primary-button>{{ __('Log time') }}</x-primary-button>
        </form>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
