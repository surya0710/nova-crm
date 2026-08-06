<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="__('Feedback Request')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Feedback'), 'href' => route('hrms.performance.feedback.index')],
                ['label' => __('Feedback Requests'), 'href' => route('hrms.performance.feedback.requests.index')],
                ['label' => __('Feedback Request'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <dl class="grid grid-cols-2 gap-3 text-sm">
            <dt class="text-slate-500">{{ __('Campaign') }}</dt><dd>{{ $feedbackRequest->campaign?->name }}</dd>
            <dt class="text-slate-500">{{ __('Subject') }}</dt><dd>{{ $feedbackRequest->subjectEmployee?->first_name }} {{ $feedbackRequest->subjectEmployee?->last_name }}</dd>
            <dt class="text-slate-500">{{ __('Your role') }}</dt><dd>{{ $participantTypes[$feedbackRequest->participant_type] ?? $feedbackRequest->participant_type }}</dd>
            <dt class="text-slate-500">{{ __('Status') }}</dt><dd>{{ $statuses[$feedbackRequest->status] ?? $feedbackRequest->status }}</dd>
            @if ($feedbackRequest->is_anonymous)
                <dt class="text-slate-500">{{ __('Anonymity') }}</dt><dd>{{ __('Your identity will not be shown') }}</dd>
            @endif
        </dl>
    </div>

    @if ($feedbackRequest->isSubmittable())
        @if ($feedbackRequest->status === 'pending')
        <form method="POST" action="{{ route('hrms.performance.feedback.requests.start', $feedbackRequest) }}" class="mb-4">
            @csrf
            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Start Feedback') }}</x-ui.button>
        </form>
        @endif

        @if (in_array($feedbackRequest->status, ['started', 'pending']))
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
            <h2 class="font-medium text-slate-800 mb-4">{{ __('Feedback Form') }}</h2>
            <form method="POST" action="{{ route('hrms.performance.feedback.requests.submit', $feedbackRequest) }}" class="space-y-6">
                @csrf
                @foreach ($questions as $index => $question)
                <div class="border-b border-slate-100 pb-4">
                    <label class="block font-medium text-slate-800 mb-1">
                        {{ $question->question_text }}
                        @if ($question->is_required)<span class="text-red-500">*</span>@endif
                    </label>
                    @if ($question->help_text)
                        <p class="text-sm text-slate-500 mb-2">{{ $question->help_text }}</p>
                    @endif
                    <input type="hidden" name="responses[{{ $index }}][feedback_question_id]" value="{{ $question->id }}" />
                    @if ($question->isRatingQuestion())
                        <select name="responses[{{ $index }}][rating]" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" @if($question->is_required) required @endif>
                            <option value="">{{ __('Select rating') }}</option>
                            @for ($r = ($question->scale_min ?? 1); $r <= ($question->scale_max ?? 5); $r++)
                                <option value="{{ $r }}">{{ $r }}</option>
                            @endfor
                        </select>
                    @else
                        <textarea name="responses[{{ $index }}][text_response]" rows="3" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" @if($question->is_required) required @endif></textarea>
                    @endif
                </div>
                @endforeach
                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Submit Feedback') }}</x-ui.button>
            </form>
        </div>
        @endif
    @elseif ($feedbackRequest->status === 'submitted')
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 mb-6">
            <p class="text-green-800 font-medium">{{ __('Feedback submitted successfully.') }}</p>
            <p class="text-sm text-green-700">{{ __('Submitted at') }}: {{ $feedbackRequest->submitted_at?->format('Y-m-d H:i') }}</p>
        </div>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
            <h2 class="font-medium text-slate-800 mb-4">{{ __('Your Responses') }}</h2>
            @foreach ($feedbackRequest->responses as $response)
                <div class="mb-4 pb-4 border-b border-slate-100">
                    <p class="font-medium text-slate-800">{{ $response->question?->question_text }}</p>
                    @if ($response->rating !== null)
                        <p class="text-sm text-slate-600">{{ __('Rating') }}: {{ $response->rating }}</p>
                    @endif
                    @if ($response->text_response)
                        <p class="text-sm text-slate-600 mt-1">{{ $response->text_response }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
    </x-layouts.entity-detail>
</x-app-layout>
