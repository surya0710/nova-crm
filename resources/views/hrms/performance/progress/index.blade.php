<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Goal Progress')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Goal Progress'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
            <div class="text-sm text-slate-500">{{ __('Active Goals') }}</div>
            <div class="text-2xl font-semibold">{{ $summary['active'] }}</div>
        </div>
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
            <div class="text-sm text-slate-500">{{ __('Completed') }}</div>
            <div class="text-2xl font-semibold">{{ $summary['completed'] }}</div>
        </div>
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
            <div class="text-sm text-slate-500">{{ __('Avg Achievement') }}</div>
            <div class="text-2xl font-semibold">{{ $summary['avg_achievement'] }}%</div>
        </div>
    </div>

    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Goal') }}</th>
                    <th class="p-3 text-left">{{ __('Value') }}</th>
                    <th class="p-3 text-left">{{ __('Achievement') }}</th>
                    <th class="p-3 text-left">{{ __('Updated By') }}</th>
                    <th class="p-3 text-left">{{ __('When') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($updates as $update)
                <tr class="border-t">
                    <td class="p-3">
                        @if ($update->goal)
                            <a class="text-sm font-medium text-primary-700 hover:text-primary-800" href="{{ route('hrms.performance.goals.show', $update->goal) }}">{{ $update->goal->title }}</a>
                        @endif
                    </td>
                    <td class="p-3">{{ $update->progress_value }}</td>
                    <td class="p-3">{{ $update->achievement_percentage }}%</td>
                    <td class="p-3">{{ $update->updater?->name }}</td>
                    <td class="p-3">{{ $update->created_at?->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $updates->links() }}</div>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
