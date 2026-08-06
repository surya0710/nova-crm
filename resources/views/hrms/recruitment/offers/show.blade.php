<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="__('Offer')"
        :subtitle="$offer->candidate?->fullName()"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Offers'), 'href' => route('hrms.recruitment.offers.index')],
                ['label' => $offer->candidate?->fullName() ?? __('Offer'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6 mb-6">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><dt class="text-slate-500">{{ __('Status') }}</dt><dd>{{ $offer->statusLabel() }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Position') }}</dt><dd>{{ $offer->jobApplication?->jobOpening?->title }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Proposed Salary') }}</dt><dd>{{ number_format((float) $offer->proposed_salary, 2) }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Joining Date') }}</dt><dd>{{ $offer->joining_date?->format('Y-m-d') }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Expiry Date') }}</dt><dd>{{ $offer->expiry_date?->format('Y-m-d') }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Reporting Manager') }}</dt><dd>{{ $offer->reportingManager?->full_name ?? '—' }}</dd></div>
        </dl>
    </div>
    @if ($offer->generated_content)
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6 mb-6">
        <h2 class="font-medium mb-2">{{ __('Generated Content') }}</h2>
        <pre class="text-sm whitespace-pre-wrap bg-slate-50 p-4 rounded-md">{{ $offer->generated_content }}</pre>
    </div>
    @endif
    @if ($offer->approvals->isNotEmpty())
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6 mb-6">
        <h2 class="font-medium mb-3">{{ __('Approval Status') }}</h2>
        <ul class="text-sm space-y-2">
            @foreach ($offer->approvals as $approval)
                <li>{{ $approval->approver?->name }} — {{ $approval->statusLabel() }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    @if ($offer->negotiations->isNotEmpty())
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6 mb-6">
        <h2 class="font-medium mb-3">{{ __('Negotiation Timeline') }}</h2>
        <ul class="text-sm space-y-2">
            @foreach ($offer->negotiations as $negotiation)
                <li>
                    <a href="{{ route('hrms.recruitment.negotiations.show', $negotiation) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ $negotiation->created_at?->format('Y-m-d') }}</a>
                    — {{ $negotiation->outcomeLabel() }}
                </li>
            @endforeach
        </ul>
    </div>
    @endif
    @can('update', $offer)
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6 space-y-4">
        @if ($offer->status === 'draft')
        <form method="POST" action="{{ route('hrms.recruitment.offers.submit', $offer) }}" class="space-y-2">
            @csrf
            <label class="text-sm text-slate-600">{{ __('Submit for Approval — Approvers') }}</label>
            <select name="approver_ids[]" multiple class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                @foreach ($approvers as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach
            </select>
            <button class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">{{ __('Submit for Approval') }}</button>
        </form>
        @endif
        @if ($offer->status === 'approved')
        <form method="POST" action="{{ route('hrms.recruitment.offers.send', $offer) }}">@csrf<button class="px-4 py-2 bg-green-600 text-white rounded-md">{{ __('Send Offer') }}</button></form>
        @endif
        @if ($offer->status === 'sent')
        <form method="POST" action="{{ route('hrms.recruitment.offers.accept', $offer) }}">@csrf<button class="px-4 py-2 bg-green-600 text-white rounded-md">{{ __('Mark Accepted') }}</button></form>
        <form method="POST" action="{{ route('hrms.recruitment.offers.reject', $offer) }}">@csrf<button class="px-4 py-2 bg-red-600 text-white rounded-md">{{ __('Mark Rejected') }}</button></form>
        @endif
        @if (in_array($offer->status, ['draft', 'pending_approval', 'approved', 'sent']))
        <form method="POST" action="{{ route('hrms.recruitment.offers.withdraw', $offer) }}">@csrf<button class="px-4 py-2 bg-slate-600 text-white rounded-md">{{ __('Withdraw') }}</button></form>
        @endif
    </div>
    @endcan
    </x-layouts.entity-detail>
</x-app-layout>
