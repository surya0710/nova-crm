<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">{{ __('Integrations') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Connect marketing providers for :org', ['org' => $organization->name]) }}</p>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="max-w-5xl space-y-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div></div>
            <a
                href="{{ route('integrations.diagnostics') }}"
                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
            >
                {{ __('Diagnostics & Health') }}
            </a>
        </div>

        <section>
            <div class="mb-4">
                <h2 class="text-base font-semibold text-slate-900">{{ __('Marketing') }}</h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ __('Each organization connects its own provider accounts. Application credentials stay on the platform; access tokens are stored encrypted per organization.') }}
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($cards as $card)
                    <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">{{ $card['name'] }}</h3>
                                @if ($card['channel'])
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $card['channel'] }}</p>
                                @endif
                            </div>
                            @php
                                $statusClasses = match ($card['status']) {
                                    'connected' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                    'expired' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
                                    'error' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                    default => 'bg-slate-50 text-slate-600 ring-slate-500/20',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $statusClasses }}">
                                {{ __($card['status_label']) }}
                            </span>
                        </div>

                        @if ($card['last_error'] && in_array($card['status'], ['error', 'expired'], true))
                            <p class="mt-3 text-xs text-rose-600 line-clamp-2">{{ $card['last_error'] }}</p>
                        @endif

                        <div class="mt-5 flex flex-wrap items-center gap-2">
                            @if ($card['connectable'])
                                @if ($card['status'] === 'disconnected')
                                    <a
                                        href="{{ route('marketing.providers.connect', ['provider' => $card['slug']]) }}"
                                        class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500"
                                    >
                                        {{ __('Connect') }}
                                    </a>
                                @else
                                    <a
                                        href="{{ route('marketing.providers.connect', ['provider' => $card['slug']]) }}"
                                        class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500"
                                    >
                                        {{ __('Reconnect') }}
                                    </a>
                                    <form method="POST" action="{{ route('integrations.disconnect', ['provider' => $card['slug']]) }}" class="inline">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                            onclick="return confirm(@js(__('Disconnect this integration? Tenant credentials will be removed.')))"
                                        >
                                            {{ __('Disconnect') }}
                                        </button>
                                    </form>
                                @endif
                            @else
                                <span class="inline-flex items-center rounded-lg border border-dashed border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-500">
                                    {{ __('Coming soon') }}
                                </span>
                            @endif

                            <a
                                href="{{ route('integrations.show', ['provider' => $card['slug']]) }}"
                                class="inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-500"
                            >
                                {{ __('View Details') }}
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    </div>
</x-app-layout>
