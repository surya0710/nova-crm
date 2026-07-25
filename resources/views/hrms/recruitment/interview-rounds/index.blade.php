<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Interview Rounds')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Interview Rounds'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
            <h2 class="font-medium mb-4">{{ __('Scheduled Interviews') }}</h2>
            <ul class="space-y-3 text-sm">
                @forelse ($rounds as $round)
                    <li class="border-b border-slate-100 pb-2">
                        <a href="{{ route('hrms.recruitment.interview-rounds.show', $round) }}" class="text-indigo-600 font-medium">
                            {{ $round->jobApplication?->candidate?->fullName() }} — {{ $round->interviewStage?->name }}
                        </a>
                        <div class="text-slate-500">{{ $round->statusLabel() }} · {{ $round->scheduled_at?->format('Y-m-d H:i') ?? __('Not scheduled') }}</div>
                    </li>
                @empty
                    <li class="text-slate-500">{{ __('No interview rounds yet.') }}</li>
                @endforelse
            </ul>
            {{ $rounds->links() }}
        </div>
        @can('create', App\Models\InterviewRound::class)
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
            <h2 class="font-medium mb-4">{{ __('Schedule Interview') }}</h2>
            <form method="POST" action="{{ route('hrms.recruitment.interview-rounds.store') }}" class="space-y-3 text-sm">
                @csrf
                <div><label class="text-slate-600">{{ __('Application') }}</label>
                    <select name="job_application_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                        @foreach ($applications as $app)
                            <option value="{{ $app->id }}">{{ $app->candidate?->fullName() }} — {{ $app->jobOpening?->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="text-slate-600">{{ __('Stage') }}</label>
                    <select name="interview_stage_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                        @foreach ($stages as $stage)<option value="{{ $stage->id }}">{{ $stage->name }}</option>@endforeach
                    </select>
                </div>
                <div><label class="text-slate-600">{{ __('Type') }}</label>
                    <select name="interview_type" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                        @foreach ($interviewTypes as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                    </select>
                </div>
                <div><label class="text-slate-600">{{ __('Scheduled At') }}</label><input type="datetime-local" name="scheduled_at" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500"></div>
                <div><label class="text-slate-600">{{ __('Duration (minutes)') }}</label><input type="number" name="duration_minutes" min="15" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500"></div>
                <div><label class="text-slate-600">{{ __('Location') }}</label><input name="location" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500"></div>
                <div><label class="text-slate-600">{{ __('Evaluation Template') }}</label>
                    <select name="evaluation_template_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">{{ __('None') }}</option>
                        @foreach ($templates as $tpl)<option value="{{ $tpl->id }}">{{ $tpl->name }}</option>@endforeach
                    </select>
                </div>
                <input type="hidden" name="status" value="scheduled">
                <input type="hidden" name="participants[0][participant_type]" value="internal">
                <div><label class="text-slate-600">{{ __('Interviewer (Employee)') }}</label>
                    <select name="participants[0][employee_id]" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">{{ __('Select employee') }}</option>
                        @foreach ($employees as $emp)<option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }}</option>@endforeach
                    </select>
                </div>
                <input type="hidden" name="participants[0][role]" value="lead_interviewer">
                <button class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">{{ __('Schedule Interview') }}</button>
            </form>
        </div>
        @endcan
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
