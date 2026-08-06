<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Negotiations')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Negotiations'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
            <ul class="space-y-2 text-sm">
                @forelse ($negotiations as $negotiation)
                    <li>
                        <a href="{{ route('hrms.recruitment.negotiations.show', $negotiation) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ $negotiation->offerLetter?->candidate?->fullName() }}</a>
                        — {{ $negotiation->created_at?->format('Y-m-d') }} · {{ $negotiation->outcomeLabel() }}
                    </li>
                @empty
                    <li class="text-slate-500">{{ __('No negotiations recorded.') }}</li>
                @endforelse
            </ul>
            {{ $negotiations->links() }}
        </div>
        @can('create', App\Models\OfferNegotiation::class)
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
            <h2 class="font-medium mb-4">{{ __('Record Negotiation') }}</h2>
            <form method="POST" action="{{ route('hrms.recruitment.negotiations.store') }}" class="space-y-3">
                @csrf
                <div><label class="text-sm text-slate-600">{{ __('Offer') }}</label>
                    <select name="offer_letter_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                        @foreach ($offers as $offer)<option value="{{ $offer->id }}">{{ $offer->candidate?->fullName() }} — {{ $offer->statusLabel() }}</option>@endforeach
                    </select>
                </div>
                <div><label class="text-sm text-slate-600">{{ __('Requested Salary') }}</label><input type="number" step="0.01" name="requested_salary" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500"></div>
                <div><label class="text-sm text-slate-600">{{ __('Requested Joining Date') }}</label><input type="date" name="requested_joining_date" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500"></div>
                <div><label class="text-sm text-slate-600">{{ __('Candidate Comments') }}</label><textarea name="candidate_comments" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" rows="2"></textarea></div>
                <div><label class="text-sm text-slate-600">{{ __('Recruiter Notes') }}</label><textarea name="recruiter_notes" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" rows="2"></textarea></div>
                <button class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">{{ __('Record') }}</button>
            </form>
        </div>
        @endcan
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
