<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Hiring Decisions')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Hiring Decisions'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
            <ul class="space-y-2 text-sm">
                @forelse ($decisions as $decision)
                    <li>
                        <a href="{{ route('hrms.recruitment.hiring-decisions.show', $decision) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ $decision->jobApplication?->candidate?->fullName() }}</a>
                        — {{ $decision->recommendationLabel() }}
                        @if ($decision->onboarding_recommended) <span class="text-green-600">({{ __('Onboarding recommended') }})</span> @endif
                    </li>
                @empty
                    <li class="text-slate-500">{{ __('No hiring decisions yet.') }}</li>
                @endforelse
            </ul>
            {{ $decisions->links() }}
        </div>
        @can('create', App\Models\HiringDecision::class)
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
            <h2 class="font-medium mb-4">{{ __('Record Decision') }}</h2>
            <form method="POST" action="{{ route('hrms.recruitment.hiring-decisions.store') }}" class="space-y-3">
                @csrf
                <div><label class="text-sm text-slate-600">{{ __('Application') }}</label>
                    <select name="job_application_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                        @foreach ($applications as $app)<option value="{{ $app->id }}">{{ $app->candidate?->fullName() }} — {{ $app->jobOpening?->title }}</option>@endforeach
                    </select>
                </div>
                <div><label class="text-sm text-slate-600">{{ __('Recommendation') }}</label>
                    <select name="recommendation" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                        @foreach ($recommendations as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div><label class="text-sm text-slate-600">{{ __('Decision Date') }}</label><input type="date" name="decision_date" value="{{ now()->toDateString() }}" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500"></div>
                <div><label class="text-sm text-slate-600">{{ __('Final Notes') }}</label><textarea name="final_notes" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" rows="3"></textarea></div>
                <button class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">{{ __('Record Decision') }}</button>
            </form>
        </div>
        @endcan
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
