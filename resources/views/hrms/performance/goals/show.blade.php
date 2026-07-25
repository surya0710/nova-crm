<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$goal->title"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Goals'), 'href' => route('hrms.performance.goals.index')],
                ['label' => $goal->title, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 rounded-xl bg-white border border-slate-200 p-5 space-y-3">
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div><span class="text-slate-500">{{ __('Status') }}</span><div class="font-medium">{{ $statuses[$goal->status] ?? $goal->status }}</div></div>
                <div><span class="text-slate-500">{{ __('Cycle') }}</span><div class="font-medium">{{ $goal->cycle?->name }}</div></div>
                <div><span class="text-slate-500">{{ __('Weight') }}</span><div class="font-medium">{{ $goal->weight }}%</div></div>
                <div><span class="text-slate-500">{{ __('Achievement') }}</span><div class="font-medium">{{ $goal->achievement_percentage }}%</div></div>
                <div><span class="text-slate-500">{{ __('Current / Target') }}</span><div class="font-medium">{{ $goal->current_value }} / {{ $goal->target_value ?? '—' }}</div></div>
                <div><span class="text-slate-500">{{ __('Due Date') }}</span><div class="font-medium">{{ $goal->due_date?->format('Y-m-d') ?? '—' }}</div></div>
            </div>
            @if ($goal->description)
                <p class="text-sm text-slate-600">{{ $goal->description }}</p>
            @endif
            <div class="flex gap-3 pt-2">
                @can('update', $goal)
                    @if ($goal->isEditable())
                        @can('updateProgress', $goal)
                        @endcan
                    @endif
                @endcan
                @if (auth()->user()?->can('update', $goal) && $goal->status !== 'completed' && $goal->status !== 'cancelled' && auth()->user()?->hasPermission('performance.goal.manage'))
                    <form method="POST" action="{{ route('hrms.performance.goals.complete', $goal) }}">@csrf <x-ui.button type="submit" variant="primary" size="sm">{{ __('Mark Completed') }}</x-ui.button></form>
                @endif
                @can('delete', $goal)
                    @if ($goal->isEditable())
                    <form method="POST" action="{{ route('hrms.performance.goals.destroy', $goal) }}">@csrf @method('DELETE') <button class="text-red-600 text-sm">{{ __('Cancel Goal') }}</button></form>
                    @endif
                @endcan
            </div>
        </div>

        @can('updateProgress', $goal)
        @if ($goal->isEditable())
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-5">
            <h2 class="font-medium text-slate-800 mb-3">{{ __('Update Progress') }}</h2>
            <form method="POST" action="{{ route('hrms.performance.goals.progress', $goal) }}" class="space-y-3">
                @csrf
                <x-forms.input name="progress_value" type="number" step="0.01" placeholder="{{ __('Progress Value') }}" required  />
                <textarea name="notes" rows="3" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Notes') }}"></textarea>
                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Save Progress') }}</x-ui.button>
            </form>
        </div>
        @endif
        @endcan
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
            <div class="p-4 border-b font-medium">{{ __('Progress History') }}</div>
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50"><tr><th class="p-3 text-left">{{ __('Value') }}</th><th class="p-3 text-left">{{ __('Achievement') }}</th><th class="p-3 text-left">{{ __('By') }}</th><th class="p-3 text-left">{{ __('When') }}</th></tr></thead>
                <tbody>
                @forelse ($goal->progressUpdates as $update)
                    <tr class="border-t">
                        <td class="p-3">{{ $update->progress_value }}</td>
                        <td class="p-3">{{ $update->achievement_percentage }}%</td>
                        <td class="p-3">{{ $update->updater?->name }}</td>
                        <td class="p-3">{{ $update->created_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td class="p-3 text-slate-500" colspan="4">{{ __('No progress updates yet.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
            <div class="p-4 border-b font-medium">{{ __('Check-ins') }}</div>
            @can('checkin', $goal)
            <form method="POST" action="{{ route('hrms.performance.checkins.store', $goal) }}" class="p-4 space-y-2 border-b">
                @csrf
                <textarea name="summary" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Summary') }}" required></textarea>
                <textarea name="progress" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Progress notes') }}"></textarea>
                <textarea name="risks" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Risks') }}"></textarea>
                <textarea name="next_steps" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Next steps') }}"></textarea>
                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Add Check-in') }}</x-ui.button>
            </form>
            @endcan
            <div class="divide-y">
                @forelse ($goal->checkins as $checkin)
                    <div class="p-4 text-sm">
                        <div class="font-medium">{{ $checkin->summary }}</div>
                        <div class="text-slate-500 mt-1">{{ $checkin->author?->name }} · {{ $checkin->created_at?->format('Y-m-d H:i') }}</div>
                        @if ($checkin->progress)<p class="mt-2">{{ __('Progress') }}: {{ $checkin->progress }}</p>@endif
                        @if ($checkin->risks)<p>{{ __('Risks') }}: {{ $checkin->risks }}</p>@endif
                        @if ($checkin->next_steps)<p>{{ __('Next steps') }}: {{ $checkin->next_steps }}</p>@endif
                    </div>
                @empty
                    <div class="p-4 text-sm text-slate-500">{{ __('No check-ins yet.') }}</div>
                @endforelse
            </div>
        </div>
    </div>
    </x-layouts.entity-detail>
</x-app-layout>
