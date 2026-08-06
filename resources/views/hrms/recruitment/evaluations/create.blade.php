<x-app-layout>
    <x-flash-messages />

    <x-layouts.create
        :title="__('Submit Evaluation')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Evaluations'), 'href' => route('hrms.recruitment.evaluations.index')],
                ['label' => __('Submit Evaluation'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6 mb-4">
        <p class="text-sm text-slate-600">{{ __('Candidate') }}: <strong>{{ $round->jobApplication?->candidate?->fullName() }}</strong></p>
    </div>
    <form method="POST" action="{{ route('hrms.recruitment.evaluations.store') }}" class="rounded-xl border border-line bg-surface-card shadow-sm p-6 space-y-4">
        @csrf
        <input type="hidden" name="interview_round_id" value="{{ $round->id }}">
        <div><label class="text-sm text-slate-600">{{ __('Interviewer') }}</label>
            <select name="interview_participant_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                @foreach ($participants as $p)<option value="{{ $p->id }}">{{ $p->displayName() }}</option>@endforeach
            </select>
        </div>
        @if ($round->evaluation_template_id)
            <input type="hidden" name="evaluation_template_id" value="{{ $round->evaluation_template_id }}">
            @foreach ($round->evaluationTemplate?->sections ?? [] as $section)
                <fieldset class="border border-slate-200 rounded-md p-4">
                    <legend class="font-medium px-1">{{ $section->title }}</legend>
                    @foreach ($section->questions as $question)
                        <div class="mt-3">
                            <label class="text-sm">{{ $question->question }} @if($question->is_required)*@endif</label>
                            @if (in_array($question->question_type, ['rating_1_5', 'rating_1_10']))
                                <input type="number" name="responses[{{ $question->id }}]" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" @if($question->is_required) required @endif>
                            @elseif ($question->question_type === 'yes_no')
                                <select name="responses[{{ $question->id }}]" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" @if($question->is_required) required @endif>
                                    <option value="">{{ __('Select') }}</option><option value="yes">{{ __('Yes') }}</option><option value="no">{{ __('No') }}</option>
                                </select>
                            @else
                                <textarea name="responses[{{ $question->id }}]" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" rows="2" @if($question->is_required) required @endif></textarea>
                            @endif
                        </div>
                    @endforeach
                </fieldset>
            @endforeach
        @endif
        <div><label class="text-sm text-slate-600">{{ __('Overall Rating') }}</label><input type="number" step="0.1" min="1" max="10" name="overall_rating" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500"></div>
        <div><label class="text-sm text-slate-600">{{ __('Recommendation') }}</label>
            <select name="recommendation" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                @foreach ($recommendations as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
            </select>
        </div>
        <div><label class="text-sm text-slate-600">{{ __('Strengths') }}</label><textarea name="strengths" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" rows="2"></textarea></div>
        <div><label class="text-sm text-slate-600">{{ __('Concerns') }}</label><textarea name="concerns" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" rows="2"></textarea></div>
        <div><label class="text-sm text-slate-600">{{ __('Summary') }}</label><textarea name="summary" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" rows="3"></textarea></div>
        <button class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">{{ __('Submit Evaluation') }}</button>
    </form>
    </x-layouts.create>
</x-app-layout>
