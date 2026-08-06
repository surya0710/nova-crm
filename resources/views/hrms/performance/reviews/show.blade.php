<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="__('Performance Review')"
        :subtitle="trim(($review->employee?->first_name ?? '').' '.($review->employee?->last_name ?? ''))"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Performance Reviews'), 'href' => route('hrms.performance.reviews.index')],
                ['label' => trim(($review->employee?->first_name ?? '').' '.($review->employee?->last_name ?? '')) ?: __('Performance Review'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 rounded-xl bg-white border border-slate-200 p-5 space-y-3 text-sm">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <div><span class="text-slate-500">{{ __('Type') }}</span><div class="font-medium">{{ $reviewTypes[$review->review_type] ?? $review->review_type }}</div></div>
                <div><span class="text-slate-500">{{ __('Status') }}</span><div class="font-medium">{{ $statuses[$review->status] ?? $review->status }}</div></div>
                <div><span class="text-slate-500">{{ __('Cycle') }}</span><div class="font-medium">{{ $review->cycle?->name }}</div></div>
                <div><span class="text-slate-500">{{ __('Template') }}</span><div class="font-medium">{{ $review->template?->name }}</div></div>
                <div><span class="text-slate-500">{{ __('Reviewer') }}</span><div class="font-medium">{{ $review->reviewer?->first_name }} {{ $review->reviewer?->last_name }}</div></div>
                <div><span class="text-slate-500">{{ __('Due') }}</span><div class="font-medium">{{ $review->assignment?->due_date?->format('Y-m-d') ?? '—' }}</div></div>
                <div><span class="text-slate-500">{{ __('Submitted') }}</span><div class="font-medium">{{ $review->submitted_at?->format('Y-m-d H:i') ?? '—' }}</div></div>
                <div><span class="text-slate-500">{{ __('Snapshot') }}</span><div class="font-medium font-mono text-xs">{{ $review->snapshot_hash ? substr($review->snapshot_hash, 0, 12).'…' : '—' }}</div></div>
            </div>
            <div class="flex flex-wrap gap-3 pt-2">
                @can('update', $review)
                    @if ($review->status === 'draft')
                    <form method="POST" action="{{ route('hrms.performance.reviews.start', $review) }}">@csrf <x-ui.button type="submit" variant="primary" size="sm">{{ __('Start Review') }}</x-ui.button></form>
                    @endif
                @endcan
                @can('markReviewed', $review)
                    @if ($review->status === 'submitted')
                    <form method="POST" action="{{ route('hrms.performance.reviews.reviewed', $review) }}">@csrf <x-ui.button type="submit" variant="primary" size="sm">{{ __('Mark Reviewed') }}</x-ui.button></form>
                    @endif
                @endcan
                @can('close', $review)
                    @if (in_array($review->status, ['submitted', 'reviewed'], true))
                    <form method="POST" action="{{ route('hrms.performance.reviews.close', $review) }}">@csrf <x-ui.button type="submit" variant="primary" size="sm">{{ __('Close Review') }}</x-ui.button></form>
                    @endif
                @endcan
            </div>
        </div>
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-5 text-sm">
            <h2 class="font-medium text-slate-800 mb-2">{{ __('Instructions') }}</h2>
            <p class="text-slate-600">{{ $review->snapshot['template']['instructions'] ?? $review->template?->instructions ?? __('No instructions.') }}</p>
        </div>
    </div>

    @php $editable = $review->isEditable() && auth()->user()?->can('update', $review); @endphp

    <form method="POST" action="{{ $editable ? route('hrms.performance.reviews.draft', $review) : '#' }}" class="space-y-6">
        @csrf

        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-5 space-y-3">
            <h2 class="font-medium text-slate-800">{{ __('Narrative') }}</h2>
            <div>
                <label class="text-sm text-slate-600">{{ __('Overall Comments') }}</label>
                <textarea name="overall_comments" rows="3" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" @disabled(! $editable)>{{ old('overall_comments', $review->overall_comments) }}</textarea>
            </div>
            <div>
                <label class="text-sm text-slate-600">{{ __('Strengths') }}</label>
                <textarea name="strengths" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" @disabled(! $editable)>{{ old('strengths', $review->strengths) }}</textarea>
            </div>
            <div>
                <label class="text-sm text-slate-600">{{ __('Improvement Areas') }}</label>
                <textarea name="improvement_areas" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" @disabled(! $editable)>{{ old('improvement_areas', $review->improvement_areas) }}</textarea>
            </div>
            <div>
                <label class="text-sm text-slate-600">{{ __('Development Notes') }}</label>
                <textarea name="development_notes" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" @disabled(! $editable)>{{ old('development_notes', $review->development_notes) }}</textarea>
            </div>
        </div>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
            <div class="p-4 border-b font-medium">{{ __('Competency Evaluation') }}</div>
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="p-3 text-left">{{ __('Competency') }}</th>
                        <th class="p-3 text-left">{{ __('Weight') }}</th>
                        <th class="p-3 text-left">{{ __('Rating') }}</th>
                        <th class="p-3 text-left">{{ __('Comments') }}</th>
                        <th class="p-3 text-left">{{ __('Reviewer Notes') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($review->competencyEvaluations as $i => $evaluation)
                    <tr class="border-t align-top">
                        <td class="p-3">
                            <div class="font-medium">{{ $evaluation->competency_name }}</div>
                            <div class="text-xs text-slate-500">{{ $evaluation->section_name }}</div>
                            <input type="hidden" name="competency_evaluations[{{ $i }}][id]" value="{{ $evaluation->id }}">
                        </td>
                        <td class="p-3">{{ $evaluation->weightage }}%</td>
                        <td class="p-3">
                            @if ($editable)
                                <select name="competency_evaluations[{{ $i }}][rating]" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                    <option value="">{{ __('Select') }}</option>
                                    @foreach ($ratingLevels as $level)
                                        <option value="{{ $level['value'] }}" @selected((string) old("competency_evaluations.$i.rating", $evaluation->rating) === (string) $level['value'])>{{ $level['value'] }} — {{ $level['label'] }}</option>
                                    @endforeach
                                </select>
                            @else
                                {{ $evaluation->rating ?? '—' }}
                            @endif
                        </td>
                        <td class="p-3">
                            @if ($editable)
                                <textarea name="competency_evaluations[{{ $i }}][comments]" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">{{ old("competency_evaluations.$i.comments", $evaluation->comments) }}</textarea>
                            @else
                                {{ $evaluation->comments ?: '—' }}
                            @endif
                        </td>
                        <td class="p-3">
                            @if ($editable)
                                <textarea name="competency_evaluations[{{ $i }}][reviewer_notes]" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">{{ old("competency_evaluations.$i.reviewer_notes", $evaluation->reviewer_notes) }}</textarea>
                            @else
                                {{ $evaluation->reviewer_notes ?: '—' }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td class="p-3 text-slate-500" colspan="5">{{ __('No competencies in the review snapshot.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
            <div class="p-4 border-b font-medium">{{ __('Goal Evaluation (Snapshot)') }}</div>
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="p-3 text-left">{{ __('Goal') }}</th>
                        <th class="p-3 text-left">{{ __('Target') }}</th>
                        <th class="p-3 text-left">{{ __('Current') }}</th>
                        <th class="p-3 text-left">{{ __('Achievement') }}</th>
                        <th class="p-3 text-left">{{ __('Weight') }}</th>
                        <th class="p-3 text-left">{{ __('Status') }}</th>
                        <th class="p-3 text-left">{{ __('Comments') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($review->goalEvaluations as $i => $evaluation)
                    <tr class="border-t align-top">
                        <td class="p-3">
                            <div class="font-medium">{{ $evaluation->goal_title }}</div>
                            @if ($evaluation->kpi_name)
                                <div class="text-xs text-slate-500">KPI: {{ $evaluation->kpi_name }}</div>
                            @endif
                            <input type="hidden" name="goal_evaluations[{{ $i }}][id]" value="{{ $evaluation->id }}">
                        </td>
                        <td class="p-3">{{ $evaluation->target_value ?? '—' }}</td>
                        <td class="p-3">{{ $evaluation->current_value ?? '—' }}</td>
                        <td class="p-3">{{ $evaluation->achievement_percentage }}%</td>
                        <td class="p-3">{{ $evaluation->weight }}%</td>
                        <td class="p-3">{{ $evaluation->completion_status }}</td>
                        <td class="p-3">
                            @if ($editable)
                                <textarea name="goal_evaluations[{{ $i }}][comments]" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">{{ old("goal_evaluations.$i.comments", $evaluation->comments) }}</textarea>
                            @else
                                {{ $evaluation->comments ?: '—' }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td class="p-3 text-slate-500" colspan="7">{{ __('No goals were snapshotted for this review.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($editable)
        <div class="flex flex-wrap gap-3">
            <x-ui.button type="submit" variant="primary" size="sm" type="submit">{{ __('Save Draft') }}</x-ui.button>
            <button formaction="{{ route('hrms.performance.reviews.submit', $review) }}" class="inline-flex items-center px-4 py-2 bg-slate-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700">{{ __('Submit Review') }}</button>
        </div>
        @endif
    </form>
    </x-layouts.entity-detail>
</x-app-layout>
