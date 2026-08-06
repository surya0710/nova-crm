<x-careers-layout>
    <h1 class="text-2xl font-semibold">{{ __('Candidate Dashboard') }}</h1>
    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="rounded-xl border bg-white p-4"><div class="text-sm text-slate-500">{{ __('Profile completion') }}</div><div class="text-2xl font-semibold mt-1">{{ $profileCompletion }}%</div></div>
        <div class="rounded-xl border bg-white p-4"><div class="text-sm text-slate-500">{{ __('Active applications') }}</div><div class="text-2xl font-semibold mt-1">{{ $applications->count() }}</div></div>
        <div class="rounded-xl border bg-white p-4"><div class="text-sm text-slate-500">{{ __('Saved jobs') }}</div><div class="text-2xl font-semibold mt-1">{{ $savedJobs->count() }}</div></div>
    </div>
    <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <section class="rounded-xl border bg-white p-4">
            <h2 class="font-semibold">{{ __('Recent applications') }}</h2>
            <ul class="mt-3 space-y-2 text-sm">@forelse($applications as $application)<li><a href="{{ route('careers.applications.show', [$organization, $application]) }}" class="text-indigo-600">{{ $application->jobOpening?->title }}</a> — {{ $application->portalStatusLabel() }}</li>@empty<li class="text-slate-500">{{ __('No applications yet.') }}</li>@endforelse</ul>
        </section>
        <section class="rounded-xl border bg-white p-4">
            <h2 class="font-semibold">{{ __('Pending offers') }}</h2>
            <ul class="mt-3 space-y-2 text-sm">@forelse($pendingOffers as $offer)<li>{{ $offer->jobApplication?->jobOpening?->title }} — {{ config('hrms.recruitment.offer_statuses.'.$offer->status, $offer->status) }}</li>@empty<li class="text-slate-500">{{ __('No pending offers.') }}</li>@endforelse</ul>
        </section>
    </div>
    <div class="mt-6 flex flex-wrap gap-3 text-sm">
        <a href="{{ route('careers.profile.edit', $organization) }}" class="rounded-lg border px-3 py-2">{{ __('Profile') }}</a>
        <a href="{{ route('careers.resumes.index', $organization) }}" class="rounded-lg border px-3 py-2">{{ __('Resumes') }}</a>
        <a href="{{ route('careers.applications.index', $organization) }}" class="rounded-lg border px-3 py-2">{{ __('Applications') }}</a>
        <a href="{{ route('careers.saved-jobs.index', $organization) }}" class="rounded-lg border px-3 py-2">{{ __('Saved jobs') }}</a>
        <a href="{{ route('careers.job-alerts.index', $organization) }}" class="rounded-lg border px-3 py-2">{{ __('Job alerts') }}</a>
        <a href="{{ route('careers.offers.index', $organization) }}" class="rounded-lg border px-3 py-2">{{ __('Offers') }}</a>
    </div>
</x-careers-layout>
