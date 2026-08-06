<x-app-layout>
    <x-flash-messages />

    <x-layouts.edit
        :title="__('Performance Configuration')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Performance Configuration'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6 max-w-2xl">
        <form method="POST" action="{{ route('hrms.performance.configuration.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Default Review Frequency') }}</label>
                <select name="default_review_frequency" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                    @foreach ($frequencies as $value => $label)
                        <option value="{{ $value }}" @selected(old('default_review_frequency', $configuration->default_review_frequency) === $value)>{{ __($label) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Rating Scale') }}</label>
                <select name="rating_scale_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">{{ __('— None —') }}</option>
                    @foreach ($ratingScales as $scale)
                        <option value="{{ $scale->id }}" @selected((string) old('rating_scale_id', $configuration->rating_scale_id) === (string) $scale->id)>{{ $scale->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Goal Weighting') }}</label>
                    <x-forms.input name="goal_weighting" type="number" step="0.01" :value="old('goal_weighting', $configuration->goal_weighting)" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Competency Weighting') }}</label>
                    <x-forms.input name="competency_weighting" type="number" step="0.01" :value="old('competency_weighting', $configuration->competency_weighting)" required />
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Review Visibility') }}</label>
                <select name="review_visibility" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                    @foreach ($visibilities as $value => $label)
                        <option value="{{ $value }}" @selected(old('review_visibility', $configuration->review_visibility) === $value)>{{ __($label) }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="calibration_enabled" value="1" @checked(old('calibration_enabled', $configuration->calibration_enabled)) />
                {{ __('Calibration enabled') }}
            </label>
            @can('update', $configuration)
                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Save Configuration') }}</x-ui.button>
            @endcan
        </form>
    </div>
    </x-layouts.edit>
</x-app-layout>
