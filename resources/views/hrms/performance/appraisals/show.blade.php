<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="__('Appraisal: :name', ['name' => $appraisal->employee?->full_name])"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Appraisals'), 'href' => route('hrms.performance.appraisals.index')],
                ['label' => __('Appraisal: :name', ['name' => $appraisal->employee?->full_name]), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
            <p class="text-sm text-slate-500">{{ __('Status') }}</p>
            <p class="text-lg font-semibold">{{ $statuses[$appraisal->status] ?? $appraisal->status }}</p>
        </div>
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
            <p class="text-sm text-slate-500">{{ __('Manager Rating') }}</p>
            <p class="text-lg font-semibold">{{ $appraisal->manager_rating ?? '—' }}</p>
        </div>
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
            <p class="text-sm text-slate-500">{{ __('Final Rating') }}</p>
            <p class="text-lg font-semibold">{{ $appraisal->final_rating ?? '—' }}</p>
            @if ($appraisal->calibrated_rating)
                <p class="text-xs text-slate-500 mt-1">{{ __('Calibrated: :rating', ['rating' => $appraisal->calibrated_rating]) }}</p>
            @endif
        </div>
    </div>

    @if ($appraisal->rating_breakdown)
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <h2 class="font-medium mb-3">{{ __('Rating Breakdown') }}</h2>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-sm">
            @foreach ($appraisal->rating_breakdown as $key => $component)
                <div class="border border-slate-100 rounded-lg p-3">
                    <p class="text-slate-500 capitalize">{{ str_replace('_', ' ', $key) }}</p>
                    <p class="font-semibold">{{ $component['score'] ?? '—' }}</p>
                    <p class="text-xs text-slate-400">{{ __('Weight') }}: {{ $component['weight'] ?? 0 }}%</p>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    @can('update', $appraisal)
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <h2 class="font-medium mb-3">{{ __('Appraisal Details') }}</h2>
        <form method="POST" action="{{ route('hrms.performance.appraisals.update', $appraisal) }}" class="space-y-3">
            @csrf @method('PUT')
            <textarea name="overall_summary" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Overall Summary') }}">{{ old('overall_summary', $appraisal->overall_summary) }}</textarea>
            <textarea name="manager_recommendation" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Manager Recommendation') }}">{{ old('manager_recommendation', $appraisal->manager_recommendation) }}</textarea>
            <textarea name="final_comments" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Final Comments') }}">{{ old('final_comments', $appraisal->final_comments) }}</textarea>
            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Save') }}</x-ui.button>
        </form>
        @if (in_array($appraisal->status, ['generated', 'in_progress']))
        <form method="POST" action="{{ route('hrms.performance.appraisals.submit', $appraisal) }}" class="mt-3">
            @csrf
            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Submit Appraisal') }}</x-ui.button>
        </form>
        @endif
    </div>
    @endcan

    @can('close', $appraisal)
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <h2 class="font-medium mb-3">{{ __('HR Review') }}</h2>
        <form method="POST" action="{{ route('hrms.performance.appraisals.hr-review', $appraisal) }}" class="space-y-3">
            @csrf
            <textarea name="hr_recommendation" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('HR Recommendation') }}">{{ old('hr_recommendation', $appraisal->hr_recommendation) }}</textarea>
            <textarea name="executive_notes" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Executive Notes') }}">{{ old('executive_notes', $appraisal->executive_notes) }}</textarea>
            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Save HR Review') }}</x-ui.button>
        </form>
        @if (in_array($appraisal->status, ['submitted', 'hr_reviewed', 'calibrated']))
        <form method="POST" action="{{ route('hrms.performance.appraisals.close', $appraisal) }}" class="mt-3">
            @csrf
            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Close Appraisal') }}</x-ui.button>
        </form>
        @endif
        <form method="POST" action="{{ route('hrms.performance.appraisals.recalculate', $appraisal) }}" class="mt-3">
            @csrf
            <button type="submit" class="text-sm text-indigo-600 hover:underline">{{ __('Recalculate Rating') }}</button>
        </form>
    </div>
    @endcan

    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <h2 class="font-medium mb-3">{{ __('Development Plan') }}</h2>
        @php $plan = $appraisal->developmentPlan; @endphp
        @can('update', $appraisal)
        <form method="POST" action="{{ route('hrms.performance.appraisals.development-plan', $appraisal) }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @csrf @method('PUT')
            <textarea name="strengths" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Strengths') }}">{{ old('strengths', $plan?->strengths) }}</textarea>
            <textarea name="improvement_areas" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Improvement Areas') }}">{{ old('improvement_areas', $plan?->improvement_areas) }}</textarea>
            <textarea name="learning_objectives" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Learning Objectives') }}">{{ old('learning_objectives', $plan?->learning_objectives) }}</textarea>
            <textarea name="required_training" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Required Training') }}">{{ old('required_training', $plan?->required_training) }}</textarea>
            <textarea name="career_aspirations" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Career Aspirations') }}">{{ old('career_aspirations', $plan?->career_aspirations) }}</textarea>
            <x-forms.input name="target_completion_date" type="date" :value="old('target_completion_date', $plan?->target_completion_date?->format('Y-m-d'))" />
            <div class="md:col-span-2"><x-ui.button type="submit" variant="primary" size="sm">{{ __('Save Development Plan') }}</x-ui.button></div>
        </form>
        @else
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            <div><dt class="text-slate-500">{{ __('Strengths') }}</dt><dd>{{ $plan?->strengths ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Improvement Areas') }}</dt><dd>{{ $plan?->improvement_areas ?? '—' }}</dd></div>
        </dl>
        @endcan
    </div>

    @can('update', $appraisal)
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
            <h3 class="font-medium mb-3">{{ __('Promotion Recommendation') }}</h3>
            <form method="POST" action="{{ route('hrms.performance.appraisals.promotion', $appraisal) }}" class="space-y-2">
                @csrf
                <select name="promotion_recommendation" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                    @foreach ($promotionLevels as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <select name="target_designation_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">{{ __('Target Designation') }}</option>
                    @foreach ($designations as $designation)
                        <option value="{{ $designation->id }}">{{ $designation->name }}</option>
                    @endforeach
                </select>
                <x-forms.input name="effective_date" type="date"  />
                <textarea name="justification" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Justification') }}"></textarea>
                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Save') }}</x-ui.button>
            </form>
        </div>
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
            <h3 class="font-medium mb-3">{{ __('Compensation Recommendation') }}</h3>
            <form method="POST" action="{{ route('hrms.performance.appraisals.compensation', $appraisal) }}" class="space-y-2">
                @csrf
                <x-forms.input name="increment_percent" type="number" step="0.01" placeholder="{{ __('Increment %') }}"  />
                <x-forms.input name="bonus_recommendation" type="number" step="0.01" placeholder="{{ __('Bonus') }}"  />
                <textarea name="adjustment_notes" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Notes') }}"></textarea>
                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Save') }}</x-ui.button>
            </form>
        </div>
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
            <h3 class="font-medium mb-3">{{ __('Succession Planning') }}</h3>
            <form method="POST" action="{{ route('hrms.performance.appraisals.succession', $appraisal) }}" class="space-y-2">
                @csrf
                <select name="readiness_level" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">{{ __('Readiness Level') }}</option>
                    @foreach ($readinessLevels as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="critical_role_flag" value="1" class="rounded border-slate-300" /> {{ __('Critical Role') }}</label>
                <textarea name="succession_notes" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Notes') }}"></textarea>
                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Save') }}</x-ui.button>
            </form>
        </div>
    </div>
    @endcan

    @if ($appraisal->talentMatrixEntry)
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mt-6">
        <h2 class="font-medium">{{ __('Talent Classification') }}: {{ $appraisal->talentMatrixEntry->classification }}</h2>
    </div>
    @endif
    </x-layouts.entity-detail>
</x-app-layout>
