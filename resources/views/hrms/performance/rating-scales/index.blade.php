<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Rating Scales')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Rating Scales'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\PerformanceRatingScale::class)
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <form method="POST" action="{{ route('hrms.performance.rating-scales.store') }}" class="space-y-3">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <x-forms.input name="name" placeholder="{{ __('Name') }}" required  />
                <x-forms.input name="code" placeholder="{{ __('Code') }}" required  />
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" /> {{ __('Default') }}</label>
                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Add Rating Scale') }}</x-ui.button>
            </div>
            <p class="text-xs text-slate-500">{{ __('Default 1–5 levels will be created. Customize after creation if needed.') }}</p>
            @foreach ($defaultLevels as $index => $level)
                <input type="hidden" name="levels[{{ $index }}][value]" value="{{ $level['value'] }}" />
                <input type="hidden" name="levels[{{ $index }}][label]" value="{{ $level['label'] }}" />
                <input type="hidden" name="levels[{{ $index }}][description]" value="{{ $level['description'] ?? '' }}" />
                <input type="hidden" name="levels[{{ $index }}][sort_order]" value="{{ $index }}" />
            @endforeach
        </form>
    </div>
    @endcan
    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Name') }}</th>
                    <th class="p-3 text-left">{{ __('Code') }}</th>
                    <th class="p-3 text-left">{{ __('Levels') }}</th>
                    <th class="p-3 text-left">{{ __('Default') }}</th>
                    <th class="p-3 text-left">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($scales as $scale)
                <tr class="border-t">
                    <td class="p-3">{{ $scale->name }}</td>
                    <td class="p-3">{{ $scale->code }}</td>
                    <td class="p-3">
                        @foreach ($scale->levels as $level)
                            <span class="inline-block mr-2">{{ $level->value }} – {{ $level->label }}</span>
                        @endforeach
                    </td>
                    <td class="p-3">{{ $scale->is_default ? __('Yes') : __('No') }}</td>
                    <td class="p-3">
                        @can('delete', $scale)
                        <form method="POST" action="{{ route('hrms.performance.rating-scales.destroy', $scale) }}">@csrf @method('DELETE') <button class="text-red-600">{{ __('Delete') }}</button></form>
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $scales->links() }}</div>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
