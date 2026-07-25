<x-careers-layout>
    <h1 class="text-2xl font-semibold">{{ __('Offers') }}</h1>
    <div class="mt-6 space-y-3">@forelse($offers as $offer)
        <div class="rounded-xl border bg-white p-4">
            <div class="font-medium">{{ $offer->jobApplication?->jobOpening?->title }}</div>
            <div class="text-sm text-slate-500 mt-1">{{ config('hrms.recruitment.offer_statuses.'.$offer->status, $offer->status) }} · {{ __('Proposed salary') }}: {{ number_format($offer->proposed_salary, 2) }}</div>
        </div>
    @empty<p class="text-slate-500">{{ __('No offers yet.') }}</p>@endforelse</div>
</x-careers-layout>
