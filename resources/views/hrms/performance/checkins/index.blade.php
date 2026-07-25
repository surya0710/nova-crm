<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Goal Check-ins')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Goal Check-ins'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('viewAny', \App\Models\Goal::class)
    @if (auth()->user()?->hasPermission('performance.goal.update'))
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <h2 class="font-medium text-slate-800 mb-3">{{ __('Record Check-in') }}</h2>
        <form method="POST" id="checkin-form" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @csrf
            <select name="goal_select" id="goal_select" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                <option value="">{{ __('Select Goal') }}</option>
                @foreach ($goals as $goal)
                    <option value="{{ $goal->id }}">{{ $goal->title }}</option>
                @endforeach
            </select>
            <textarea name="summary" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Summary') }}" required></textarea>
            <textarea name="progress" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Progress') }}"></textarea>
            <textarea name="risks" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Risks') }}"></textarea>
            <textarea name="next_steps" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Next Steps') }}"></textarea>
            <div><x-ui.button type="submit" variant="primary" size="sm" type="submit">{{ __('Save Check-in') }}</x-ui.button></div>
        </form>
    </div>
    <script>
        document.getElementById('checkin-form')?.addEventListener('submit', function (e) {
            const goalId = document.getElementById('goal_select')?.value;
            if (!goalId) {
                e.preventDefault();
                return;
            }
            this.action = @json(url('hrms/performance/checkins')) + '/' + goalId;
        });
    </script>
    @endif
    @endcan

    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Goal') }}</th>
                    <th class="p-3 text-left">{{ __('Summary') }}</th>
                    <th class="p-3 text-left">{{ __('By') }}</th>
                    <th class="p-3 text-left">{{ __('When') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($checkins as $checkin)
                <tr class="border-t">
                    <td class="p-3">
                        @if ($checkin->goal)
                            <a class="text-sm font-medium text-primary-700 hover:text-primary-800" href="{{ route('hrms.performance.goals.show', $checkin->goal) }}">{{ $checkin->goal->title }}</a>
                        @endif
                    </td>
                    <td class="p-3">{{ $checkin->summary }}</td>
                    <td class="p-3">{{ $checkin->author?->name }}</td>
                    <td class="p-3">{{ $checkin->created_at?->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $checkins->links() }}</div>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
