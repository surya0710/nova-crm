<x-app-layout>
    <x-flash-messages />

    <x-layouts.settings
        :title="__('Background Verification')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Background Verification'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @php
        $verifications = $verifications ?? collect();
        $decisions = $decisions ?? collect();
    @endphp

    <div class="mb-4">
        <a href="{{ route('hrms.recruitment.integrations.index') }}" class="text-sm text-indigo-600">{{ __('← Integrations') }}</a>
    </div>

    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <h2 class="font-medium mb-3">{{ __('Submit Verification') }}</h2>
        <form method="POST" action="{{ route('hrms.recruitment.background-verification.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @csrf
            @if ($decisions->isNotEmpty())
                <select name="hiring_decision_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                    <option value="">{{ __('Hiring Decision') }}</option>
                    @foreach ($decisions as $decision)
                        <option value="{{ $decision->id }}">
                            #{{ $decision->id }}
                            @if ($decision->candidate)
                                — {{ $decision->candidate->fullName() }}
                            @endif
                        </option>
                    @endforeach
                </select>
            @else
                <x-forms.input name="hiring_decision_id" type="number" placeholder="{{ __('Hiring Decision ID') }}" required  />
            @endif
            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Submit') }}</x-ui.button>
        </form>
    </div>

    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('ID') }}</th>
                    <th class="p-3 text-left">{{ __('Candidate') }}</th>
                    <th class="p-3 text-left">{{ __('Decision') }}</th>
                    <th class="p-3 text-left">{{ __('Status') }}</th>
                    <th class="p-3 text-left">{{ __('External ID') }}</th>
                    <th class="p-3 text-left">{{ __('Last Error') }}</th>
                    <th class="p-3 text-left">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($verifications as $verification)
                <tr class="border-t">
                    <td class="p-3">{{ $verification->id }}</td>
                    <td class="p-3">{{ $verification->candidate_id ?? '—' }}</td>
                    <td class="p-3">{{ $verification->hiring_decision_id ?? '—' }}</td>
                    <td class="p-3">{{ method_exists($verification, 'statusLabel') ? $verification->statusLabel() : $verification->status }}</td>
                    <td class="p-3">{{ $verification->external_verification_id ?? '—' }}</td>
                    <td class="p-3 text-rose-600 max-w-xs truncate">{{ $verification->last_error ?? '—' }}</td>
                    <td class="p-3">
                        <div class="flex flex-wrap gap-2">
                            @if (! in_array($verification->status, ['completed', 'cancelled'], true))
                                <form method="POST" action="{{ route('hrms.recruitment.background-verification.refresh', $verification) }}">
                                    @csrf
                                    <button type="submit" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ __('Refresh') }}</button>
                                </form>
                                <form method="POST" action="{{ route('hrms.recruitment.background-verification.cancel', $verification) }}">
                                    @csrf
                                    <button type="submit" class="text-rose-600">{{ __('Cancel') }}</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr class="border-t"><td class="p-3 text-slate-500" colspan="7">{{ __('No verifications yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        @if (method_exists($verifications, 'links'))
            <div class="p-4">{{ $verifications->links() }}</div>
        @endif
    </div>
    </x-layouts.settings>
</x-app-layout>
