<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Offer Approvals')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Offer Approvals'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
        <form method="GET" class="mb-4 flex gap-2 flex-wrap">
            <select name="status" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">{{ __('All statuses') }}</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($filterStatus === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <label class="flex items-center gap-1 text-sm"><input type="checkbox" name="mine" value="1" @checked($filterMine)> {{ __('My approvals') }}</label>
            <button class="px-3 py-1 bg-slate-100 rounded-md text-sm">{{ __('Filter') }}</button>
        </form>
        <ul class="space-y-2 text-sm">
            @forelse ($approvals as $approval)
                <li>
                    <a href="{{ route('hrms.recruitment.offer-approvals.show', $approval) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ $approval->offerLetter?->candidate?->fullName() }}</a>
                    — {{ $approval->approver?->name }} · {{ $approval->statusLabel() }}
                </li>
            @empty
                <li class="text-slate-500">{{ __('No approvals pending.') }}</li>
            @endforelse
        </ul>
        {{ $approvals->links() }}
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
