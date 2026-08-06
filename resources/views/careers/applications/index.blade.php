<x-careers-layout>
    <h1 class="text-2xl font-semibold">{{ __('My applications') }}</h1>
    <div class="mt-6 space-y-3">@forelse($applications as $application)
        <a href="{{ route('careers.applications.show', [$organization, $application]) }}" class="block rounded-xl border bg-white p-4 hover:border-indigo-300">
            <div class="font-medium">{{ $application->jobOpening?->title }}</div>
            <div class="text-sm text-slate-500 mt-1">{{ $application->portalStatusLabel() }} · {{ $application->applied_date?->format('M j, Y') }}@if($application->is_draft) · {{ __('Draft') }}@endif</div>
        </a>
    @empty<p class="text-slate-500">{{ __('No applications yet.') }}</p>@endforelse</div>
</x-careers-layout>
