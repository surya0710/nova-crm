<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Recruitment Integrations')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Recruitment Integrations'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @php
        $summary = $diagnostics['summary'] ?? [];
        $retryQueue = $diagnostics['retry_queue'] ?? [];
    @endphp

    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
            <div class="text-sm text-slate-600">
                <span>{{ __('Connected') }}: {{ $summary['connected'] ?? 0 }}/{{ $summary['total'] ?? 0 }}</span>
                <span class="mx-2">·</span>
                <span>{{ __('With errors') }}: {{ $summary['with_errors'] ?? 0 }}</span>
                <span class="mx-2">·</span>
                <span>{{ __('Job board failures') }}: {{ $retryQueue['job_board_failures'] ?? 0 }}</span>
                <span class="mx-2">·</span>
                <span>{{ __('Webhook failures') }}: {{ $retryQueue['webhook_failures'] ?? 0 }}</span>
            </div>
            <form method="POST" action="{{ route('hrms.recruitment.integrations.retries') }}">
                @csrf
                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Process Retries') }}</x-ui.button>
            </form>
        </div>
        <div class="flex flex-wrap gap-3 text-sm">
            <a href="{{ route('hrms.recruitment.communication-templates.index') }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ __('Communication Templates') }}</a>
            <a href="{{ route('hrms.recruitment.calendar.index') }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ __('Calendar') }}</a>
            <a href="{{ route('hrms.recruitment.job-boards.index') }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ __('Job Boards') }}</a>
            <a href="{{ route('hrms.recruitment.resume-parsing.index') }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ __('Resume Parsing') }}</a>
            <a href="{{ route('hrms.recruitment.background-verification.index') }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ __('Background Verification') }}</a>
            <a href="{{ route('hrms.recruitment.api-access.index') }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ __('API Access') }}</a>
            <a href="{{ route('hrms.recruitment.webhooks.index') }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ __('Webhooks') }}</a>
        </div>
    </div>

    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Provider') }}</th>
                    <th class="p-3 text-left">{{ __('Category') }}</th>
                    <th class="p-3 text-left">{{ __('Status') }}</th>
                    <th class="p-3 text-left">{{ __('Last Error') }}</th>
                    <th class="p-3 text-left">{{ __('Last Synced') }}</th>
                    <th class="p-3 text-left">{{ __('Credential Expires') }}</th>
                    <th class="p-3 text-left">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($cards as $card)
                <tr class="border-t">
                    <td class="p-3">{{ $card['name'] ?? $card['slug'] ?? '—' }}</td>
                    <td class="p-3">{{ $card['category'] ?? '—' }}</td>
                    <td class="p-3">{{ $card['status'] ?? '—' }}</td>
                    <td class="p-3 text-rose-600 max-w-xs truncate">{{ $card['last_error'] ?? '—' }}</td>
                    <td class="p-3">{{ $card['last_synced_at'] ?? '—' }}</td>
                    <td class="p-3">{{ $card['credential_expires_at'] ?? '—' }}</td>
                    <td class="p-3">
                        @if (! empty($card['coming_soon']))
                            <span class="text-slate-500">{{ __('Coming soon') }}</span>
                        @elseif (! empty($card['connected']) && ! empty($card['provider_id']))
                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('hrms.recruitment.integrations.health-check', ['recruitment_provider' => $card['provider_id']]) }}">
                                    @csrf
                                    <button type="submit" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ __('Health Check') }}</button>
                                </form>
                                <form method="POST" action="{{ route('hrms.recruitment.integrations.disconnect', ['recruitment_provider' => $card['provider_id']]) }}">
                                    @csrf
                                    <button type="submit" class="text-rose-600">{{ __('Disconnect') }}</button>
                                </form>
                            </div>
                        @else
                            <form method="POST" action="{{ route('hrms.recruitment.integrations.connect', ['provider' => $card['slug']]) }}">
                                @csrf
                                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Connect') }}</x-ui.button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr class="border-t"><td class="p-3 text-slate-500" colspan="7">{{ __('No providers configured.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
