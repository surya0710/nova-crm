<x-careers-layout>
    <h1 class="text-2xl font-semibold">{{ __('Saved jobs') }}</h1>
    <div class="mt-6 space-y-3">@forelse($savedJobs as $saved)
        <a href="{{ route('careers.jobs.show', [$organization, $saved->jobOpening]) }}" class="block rounded-xl border bg-white p-4">{{ $saved->jobOpening?->title }}</a>
    @empty<p class="text-slate-500">{{ __('No saved jobs.') }}</p>@endforelse</div>
</x-careers-layout>
