<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ __('Diagnostics & Health') }}</h1>
                <p class="text-sm text-slate-500">{{ __('Provider operational visibility for :org', ['org' => $organization->name]) }}</p>
            </div>
            <a href="{{ route('integrations.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                {{ __('Back to Integrations') }}
            </a>
        </div>
    </x-slot>

    <x-flash-messages />

    @php
        $summary = $diagnostics['summary'] ?? [];
        $providers = $diagnostics['providers'] ?? [];

        $healthClasses = fn (string $state) => match ($state) {
            'healthy' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            'degraded' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
            'unhealthy', 'expired_credentials', 'revoked_credentials' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
            default => 'bg-slate-50 text-slate-600 ring-slate-500/20',
        };

        $connectionClasses = fn (string $status) => match ($status) {
            'connected' => 'text-emerald-600',
            'expired' => 'text-amber-600',
            'error' => 'text-rose-600',
            default => 'text-slate-500',
        };

        $formatTimestamp = fn (?string $value) => $value
            ? \Illuminate\Support\Carbon::parse($value)->toDayDateTimeString()
            : '—';
    @endphp

    <div class="max-w-6xl space-y-6">
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">{{ __('Overview') }}</h2>
            <dl class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-7">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Providers') }}</dt>
                    <dd class="mt-1 text-lg font-semibold text-slate-900">{{ $summary['total'] ?? 0 }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Connected') }}</dt>
                    <dd class="mt-1 text-lg font-semibold text-emerald-600">{{ $summary['connected'] ?? 0 }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Healthy') }}</dt>
                    <dd class="mt-1 text-lg font-semibold text-emerald-600">{{ $summary['healthy'] ?? 0 }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Degraded') }}</dt>
                    <dd class="mt-1 text-lg font-semibold text-amber-600">{{ $summary['degraded'] ?? 0 }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Unhealthy') }}</dt>
                    <dd class="mt-1 text-lg font-semibold text-rose-600">{{ $summary['unhealthy'] ?? 0 }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Expired') }}</dt>
                    <dd class="mt-1 text-lg font-semibold text-amber-600">{{ $summary['expired'] ?? 0 }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Revoked') }}</dt>
                    <dd class="mt-1 text-lg font-semibold text-rose-600">{{ $summary['revoked'] ?? 0 }}</dd>
                </div>
            </dl>
            <p class="mt-4 text-xs text-slate-500">
                {{ __('Generated at :time', ['time' => \Illuminate\Support\Carbon::parse($diagnostics['generated_at'] ?? now())->toDayDateTimeString()]) }}
            </p>
        </section>

        <div class="space-y-4">
            @foreach ($providers as $provider)
                @php
                    $health = $provider['health'] ?? [];
                    $connection = $provider['connection'] ?? [];
                    $credentials = $provider['credentials'] ?? [];
                    $sync = $provider['synchronization'] ?? [];
                    $stats = $provider['statistics'] ?? [];
                    $highlights = $provider['highlights'] ?? [];
                    $errors = $provider['errors'] ?? [];
                @endphp

                <article class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h3 class="text-base font-semibold text-slate-900">{{ $provider['name'] }}</h3>
                                @if (! empty($provider['channel']))
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $provider['channel'] }}</p>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $connectionClasses($connection['status'] ?? 'disconnected') }}">
                                    {{ __($connection['status_label'] ?? 'Disconnected') }}
                                </span>
                                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $healthClasses($health['state'] ?? 'disconnected') }}">
                                    {{ __($health['label'] ?? 'Disconnected') }}
                                </span>
                            </div>
                        </div>

                        <dl class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Last Upload') }}</dt>
                                <dd class="mt-1 text-sm text-slate-900">{{ $formatTimestamp($highlights['last_upload_at'] ?? null) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Last Import') }}</dt>
                                <dd class="mt-1 text-sm text-slate-900">{{ $formatTimestamp($highlights['last_import_at'] ?? null) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Last Health Check') }}</dt>
                                <dd class="mt-1 text-sm text-slate-900">{{ $formatTimestamp($highlights['last_health_check_at'] ?? null) }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="grid gap-6 px-6 py-5 lg:grid-cols-2">
                        <section>
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Credentials') }}</h4>
                            <dl class="mt-3 space-y-2 text-sm">
                                <div class="flex justify-between gap-4">
                                    <dt class="text-slate-500">{{ __('OAuth connected') }}</dt>
                                    <dd class="font-medium text-slate-900">{{ ($credentials['oauth_connected'] ?? false) ? __('Yes') : __('No') }}</dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-slate-500">{{ __('Token expires') }}</dt>
                                    <dd class="font-medium text-slate-900">{{ $formatTimestamp($credentials['expires_at'] ?? null) }}</dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-slate-500">{{ __('Refresh token') }}</dt>
                                    <dd class="font-medium text-slate-900">{{ ($credentials['refresh_token_available'] ?? false) ? __('Available') : __('Not available') }}</dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-slate-500">{{ __('Last refresh') }}</dt>
                                    <dd class="font-medium text-slate-900">{{ $formatTimestamp($credentials['last_refresh_at'] ?? null) }}</dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-slate-500">{{ __('Revoked') }}</dt>
                                    <dd class="font-medium text-slate-900">{{ ($credentials['is_revoked'] ?? false) ? __('Yes') : __('No') }}</dd>
                                </div>
                            </dl>
                        </section>

                        <section>
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Statistics') }}</h4>
                            <dl class="mt-3 space-y-2 text-sm">
                                <div class="flex justify-between gap-4">
                                    <dt class="text-slate-500">{{ __('Imported leads') }}</dt>
                                    <dd class="font-medium text-slate-900">{{ $stats['inbound']['imported_leads'] ?? 0 }}</dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-slate-500">{{ __('Webhook events processed') }}</dt>
                                    <dd class="font-medium text-slate-900">{{ $stats['inbound']['webhook_events_processed'] ?? 0 }}</dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-slate-500">{{ __('Uploaded conversions') }}</dt>
                                    <dd class="font-medium text-slate-900">{{ $stats['outbound']['uploaded_conversions'] ?? 0 }}</dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-slate-500">{{ __('Synchronizations') }}</dt>
                                    <dd class="font-medium text-slate-900">
                                        {{ $stats['general']['synchronization_count'] ?? 0 }}
                                        <span class="text-xs font-normal text-slate-500">
                                            ({{ $stats['general']['success_count'] ?? 0 }} {{ __('ok') }},
                                            {{ $stats['general']['failure_count'] ?? 0 }} {{ __('failed') }})
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        @if (! empty($sync['last']) || ! empty($sync['last_successful']) || ! empty($sync['last_failed']))
                            <section class="lg:col-span-2">
                                <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Synchronization Summary') }}</h4>
                                <div class="mt-3 overflow-x-auto">
                                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                                        <thead>
                                            <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                                                <th class="py-2 pr-4">{{ __('Scope') }}</th>
                                                <th class="py-2 pr-4">{{ __('Type') }}</th>
                                                <th class="py-2 pr-4">{{ __('Status') }}</th>
                                                <th class="py-2 pr-4">{{ __('Duration') }}</th>
                                                <th class="py-2 pr-4">{{ __('Processed') }}</th>
                                                <th class="py-2">{{ __('Failed') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach ([
                                                'last' => __('Last'),
                                                'last_successful' => __('Last successful'),
                                                'last_failed' => __('Last failed'),
                                            ] as $key => $label)
                                                @php $run = $sync[$key] ?? null; @endphp
                                                @if ($run)
                                                    <tr>
                                                        <td class="py-2 pr-4 text-slate-700">{{ $label }}</td>
                                                        <td class="py-2 pr-4 text-slate-900">{{ str_replace('_', ' ', $run['sync_type']) }}</td>
                                                        <td class="py-2 pr-4 text-slate-900">{{ $run['status'] }}</td>
                                                        <td class="py-2 pr-4 text-slate-900">
                                                            {{ $run['duration_seconds'] !== null ? $run['duration_seconds'].'s' : '—' }}
                                                        </td>
                                                        <td class="py-2 pr-4 text-slate-900">{{ $run['records_processed'] ?? 0 }}</td>
                                                        <td class="py-2 text-slate-900">{{ $run['records_failed'] ?? 0 }}</td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        @endif

                        @if (! empty($errors))
                            <section class="lg:col-span-2">
                                <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Recent Errors') }}</h4>
                                <ul class="mt-3 space-y-2">
                                    @foreach ($errors as $error)
                                        <li class="rounded-lg border border-rose-100 bg-rose-50 px-3 py-2 text-sm text-rose-800">
                                            <span class="text-xs font-medium uppercase tracking-wide text-rose-600">{{ str_replace('_', ' ', $error['type'] ?? 'error') }}</span>
                                            <p class="mt-1">{{ $error['message'] }}</p>
                                            @if (! empty($error['occurred_at']))
                                                <p class="mt-1 text-xs text-rose-600">{{ $formatTimestamp($error['occurred_at']) }}</p>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </section>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 px-6 py-4">
                        <a
                            href="{{ route('integrations.show', ['provider' => $provider['slug']]) }}"
                            class="inline-flex items-center rounded-lg px-3 py-2 text-xs font-semibold text-indigo-600 hover:text-indigo-500"
                        >
                            {{ __('View Details') }}
                        </a>

                        @if (($provider['connectable'] ?? false) && ($provider['provider_id'] ?? null))
                            <form method="POST" action="{{ route('integrations.health-check', ['provider' => $provider['slug']]) }}">
                                @csrf
                                <button
                                    type="submit"
                                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                >
                                    {{ __('Run Health Check') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</x-app-layout>
