<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="__('Offer Approval')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Offer Approvals'), 'href' => route('hrms.recruitment.offer-approvals.index')],
                ['label' => __('Offer Approval'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6 mb-6">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><dt class="text-slate-500">{{ __('Candidate') }}</dt><dd>{{ $approval->offerLetter?->candidate?->fullName() }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Approver') }}</dt><dd>{{ $approval->approver?->name }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Status') }}</dt><dd>{{ $approval->statusLabel() }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Offer Status') }}</dt><dd>{{ $approval->offerLetter?->statusLabel() }}</dd></div>
        </dl>
        @if ($approval->comments)<p class="text-sm mt-4 text-slate-600">{{ $approval->comments }}</p>@endif
    </div>
    @can('update', $approval)
    @if ($approval->status === 'pending')
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6 space-y-4">
        <form method="POST" action="{{ route('hrms.recruitment.offer-approvals.approve', $approval) }}" class="space-y-2">
            @csrf
            <textarea name="comments" placeholder="{{ __('Comments (optional)') }}" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
            <button class="px-4 py-2 bg-green-600 text-white rounded-md">{{ __('Approve') }}</button>
        </form>
        <form method="POST" action="{{ route('hrms.recruitment.offer-approvals.reject', $approval) }}" class="space-y-2">
            @csrf
            <textarea name="comments" placeholder="{{ __('Rejection reason') }}" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
            <button class="px-4 py-2 bg-red-600 text-white rounded-md">{{ __('Reject') }}</button>
        </form>
        <form method="POST" action="{{ route('hrms.recruitment.offer-approvals.return', $approval) }}" class="space-y-2">
            @csrf
            <textarea name="comments" placeholder="{{ __('Revision notes') }}" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
            <button class="px-4 py-2 bg-amber-600 text-white rounded-md">{{ __('Return for Revision') }}</button>
        </form>
    </div>
    @endif
    @endcan
    </x-layouts.entity-detail>
</x-app-layout>
