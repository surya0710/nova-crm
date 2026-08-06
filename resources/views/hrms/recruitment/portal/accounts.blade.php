<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Candidate Accounts')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Candidate Accounts'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left"><tr><th class="px-4 py-3">{{ __('Email') }}</th><th class="px-4 py-3">{{ __('Candidate') }}</th><th class="px-4 py-3">{{ __('Last login') }}</th><th class="px-4 py-3">{{ __('Registered') }}</th></tr></thead>
            <tbody>@forelse($accounts as $account)
                <tr class="border-t"><td class="px-4 py-3">{{ $account->email }}</td><td class="px-4 py-3">{{ $account->candidate?->fullName() }}</td><td class="px-4 py-3">{{ $account->last_login_at?->format('M j, Y H:i') ?? '—' }}</td><td class="px-4 py-3">{{ $account->created_at?->format('M j, Y') }}</td></tr>
            @empty<tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">{{ __('No candidate accounts yet.') }}</td></tr>@endforelse</tbody>
        </table>
        <div class="p-4">{{ $accounts->links() }}</div>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
