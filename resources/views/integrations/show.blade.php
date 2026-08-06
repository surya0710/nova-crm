<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ $integration['name'] }}</h1>
                <p class="text-sm text-slate-500">{{ __('Integration details for :org', ['org' => $organization->name]) }}</p>
            </div>
            <a href="{{ route('integrations.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                {{ __('Back to Integrations') }}
            </a>
        </div>
    </x-slot>

    <x-flash-messages />

    @php
        $assets = $assetDiscovery['assets'] ?? [];
        $selected = $selectedAssets ?? [];
        $selectedLeadForms = collect($selected['lead_form_ids'] ?? [])->map(fn ($id) => (string) $id)->all();
        $selectedConversionActions = collect($selected['conversion_action_ids'] ?? [])->map(fn ($id) => (string) $id)->all();
        $discoveryOk = ($assetDiscovery['ok'] ?? false) === true;
        $hasMetaAssets = array_key_exists('businesses', $assets);
        $hasGoogleAssets = array_key_exists('customers', $assets);
    @endphp

    <div class="max-w-3xl space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Status') }}</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-900">{{ __($integration['status_label']) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Provider') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $integration['slug'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('External account') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $integration['external_account_id'] ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Token expires') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">
                        {{ $integration['expires_at'] ? \Illuminate\Support\Carbon::parse($integration['expires_at'])->toDayDateTimeString() : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Connected at') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">
                        {{ $integration['connected_at'] ? \Illuminate\Support\Carbon::parse($integration['connected_at'])->toDayDateTimeString() : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Disconnected at') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">
                        {{ $integration['disconnected_at'] ? \Illuminate\Support\Carbon::parse($integration['disconnected_at'])->toDayDateTimeString() : '—' }}
                    </dd>
                </div>
            </dl>

            @if (! empty($integration['last_error']))
                <div class="mt-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    {{ $integration['last_error'] }}
                </div>
            @endif

            <p class="mt-6 text-xs text-slate-500">
                {{ __('Access tokens and refresh tokens are encrypted at rest and are never shown in this UI.') }}
            </p>

            <div class="mt-6 flex flex-wrap gap-2">
                @if ($integration['connectable'])
                    <a
                        href="{{ route('marketing.providers.connect', ['provider' => $integration['slug']]) }}"
                        class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500"
                    >
                        {{ $integration['status'] === 'disconnected' ? __('Connect') : __('Reconnect') }}
                    </a>
                    @if ($integration['status'] !== 'disconnected')
                        <form method="POST" action="{{ route('integrations.disconnect', ['provider' => $integration['slug']]) }}">
                            @csrf
                            <button
                                type="submit"
                                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                onclick="return confirm(@js(__('Disconnect this integration?')))"
                            >
                                {{ __('Disconnect') }}
                            </button>
                        </form>
                    @endif
                @else
                    <span class="inline-flex items-center rounded-lg border border-dashed border-slate-300 px-3 py-2 text-xs font-medium text-slate-500">
                        {{ __('Coming soon') }}
                    </span>
                @endif
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">{{ __('Synchronization History') }}</h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ __('Provider synchronization execution history for this organization.') }}
                </p>
            </div>

            @if (($synchronizationHistory ?? collect())->isEmpty())
                <p class="mt-4 text-sm text-slate-500">{{ __('No synchronization runs yet.') }}</p>
            @else
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead>
                            <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                                <th class="py-2 pr-4">{{ __('Type') }}</th>
                                <th class="py-2 pr-4">{{ __('Direction') }}</th>
                                <th class="py-2 pr-4">{{ __('Status') }}</th>
                                <th class="py-2 pr-4">{{ __('Started') }}</th>
                                <th class="py-2 pr-4">{{ __('Finished') }}</th>
                                <th class="py-2 pr-4">{{ __('Processed') }}</th>
                                <th class="py-2">{{ __('Failed') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($synchronizationHistory as $run)
                                <tr>
                                    <td class="py-3 pr-4 font-medium text-slate-900">
                                        {{ __(str($run->sync_type)->replace('_', ' ')->title()->toString()) }}
                                    </td>
                                    <td class="py-3 pr-4 text-slate-700">{{ __(ucfirst($run->direction)) }}</td>
                                    <td class="py-3 pr-4 text-slate-700">{{ __(ucfirst($run->status)) }}</td>
                                    <td class="py-3 pr-4 text-slate-700">
                                        {{ $run->started_at?->toDayDateTimeString() ?: '—' }}
                                    </td>
                                    <td class="py-3 pr-4 text-slate-700">
                                        {{ $run->finished_at?->toDayDateTimeString() ?: '—' }}
                                    </td>
                                    <td class="py-3 pr-4 text-slate-700">{{ $run->records_processed }}</td>
                                    <td class="py-3 text-slate-700">{{ $run->records_failed }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if ($supportsAssetDiscovery && $integration['status'] !== 'disconnected')
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">{{ __('Business assets') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ __('Choose which provider assets :product should manage. Nothing is assumed automatically.', ['product' => config('branding.product_name')]) }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('integrations.assets.refresh', ['provider' => $integration['slug']]) }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            {{ __('Refresh Assets') }}
                        </button>
                    </form>
                </div>

                @if (! empty($selected) && is_array($selected))
                    <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <h3 class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Saved selections') }}</h3>
                        <dl class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            @foreach ([
                                'business_id' => __('Business Manager'),
                                'ad_account_id' => __('Ad Account'),
                                'page_id' => __('Page'),
                                'pixel_id' => __('Pixel'),
                                'customer_id' => __('Customer Account'),
                            ] as $key => $label)
                                @if (! empty($selected[$key]))
                                    <div>
                                        <dt class="text-xs text-slate-500">{{ $label }}</dt>
                                        <dd class="text-sm text-slate-900 break-all">{{ $selected[$key] }}</dd>
                                    </div>
                                @endif
                            @endforeach
                            @if (! empty($selectedLeadForms))
                                <div class="sm:col-span-2">
                                    <dt class="text-xs text-slate-500">{{ __('Lead Forms') }}</dt>
                                    <dd class="text-sm text-slate-900 break-all">{{ implode(', ', $selectedLeadForms) }}</dd>
                                </div>
                            @endif
                            @if (! empty($selectedConversionActions))
                                <div class="sm:col-span-2">
                                    <dt class="text-xs text-slate-500">{{ __('Conversion Actions') }}</dt>
                                    <dd class="text-sm text-slate-900 break-all">{{ implode(', ', $selectedConversionActions) }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                @endif

                @if (! $discoveryOk)
                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        {{ $assetDiscovery['message'] ?? __('Unable to load assets from the provider.') }}
                        <p class="mt-1 text-xs">{{ __('Previously saved selections are kept until you successfully refresh and save again.') }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('integrations.assets.save', ['provider' => $integration['slug']]) }}" class="mt-6 space-y-5">
                    @csrf

                    @if ($hasGoogleAssets)
                    <div>
                        <label for="customer_id" class="block text-xs font-medium uppercase tracking-wide text-slate-500">
                            {{ __('Customer Account') }}
                        </label>
                        <select
                            id="customer_id"
                            name="customer_id"
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">{{ __('Select a Customer Account') }}</option>
                            @foreach ($assets['customers'] as $customer)
                                <option
                                    value="{{ $customer['id'] }}"
                                    @selected(($selected['customer_id'] ?? null) === $customer['id'])
                                >
                                    {{ $customer['descriptive_name'] ?: $customer['id'] }}
                                    ({{ $customer['id'] }})
                                    @if ($customer['manager'] ?? false)
                                        — {{ __('Manager') }}
                                    @endif
                                    @if (! empty($customer['currency_code']))
                                        · {{ $customer['currency_code'] }}
                                    @endif
                                    @if (! empty($customer['time_zone']))
                                        · {{ $customer['time_zone'] }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <p class="block text-xs font-medium uppercase tracking-wide text-slate-500">
                            {{ __('Conversion Actions') }}
                        </p>
                        @if (empty($assets['conversion_actions']))
                            <p class="mt-2 text-sm text-slate-500">{{ __('No conversion actions were discovered for the accessible customer accounts.') }}</p>
                        @else
                            <div class="mt-2 space-y-2 rounded-lg border border-slate-200 p-3">
                                @foreach ($assets['conversion_actions'] as $action)
                                    <label class="flex items-start gap-2 text-sm {{ ($action['active'] ?? true) ? 'text-slate-800' : 'text-slate-400' }}">
                                        <input
                                            type="checkbox"
                                            name="conversion_action_ids[]"
                                            value="{{ $action['id'] }}"
                                            class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                            @checked(in_array((string) $action['id'], $selectedConversionActions, true))
                                            @disabled($action['missing'] ?? false)
                                        >
                                        <span>
                                            <span class="font-medium">{{ $action['name'] ?: $action['id'] }}</span>
                                            @if (! ($action['active'] ?? true))
                                                <span class="ml-1 inline-flex items-center rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                                    {{ ($action['missing'] ?? false) ? __('Removed') : __('Inactive') }}
                                                </span>
                                            @endif
                                            <span class="block text-xs text-slate-500">
                                                @if (! empty($action['customer_id']))
                                                    {{ __('Customer') }}: {{ $action['customer_id'] }}
                                                @endif
                                                @if (! empty($action['category']))
                                                    · {{ $action['category'] }}
                                                @endif
                                                @if (! empty($action['type']))
                                                    · {{ $action['type'] }}
                                                @endif
                                                @if ($action['primary_for_goal'] ?? false)
                                                    · {{ __('Primary for goal') }}
                                                @endif
                                            </span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @endif

                    @if ($hasMetaAssets)
                    <div>
                        <label for="business_id" class="block text-xs font-medium uppercase tracking-wide text-slate-500">
                            {{ __('Business Manager') }}
                        </label>
                        <select
                            id="business_id"
                            name="business_id"
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">{{ __('Select a Business Manager') }}</option>
                            @foreach ($assets['businesses'] as $business)
                                <option
                                    value="{{ $business['id'] }}"
                                    @selected(($selected['business_id'] ?? null) === $business['id'])
                                >
                                    {{ $business['name'] ?: $business['id'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="ad_account_id" class="block text-xs font-medium uppercase tracking-wide text-slate-500">
                            {{ __('Ad Account') }}
                        </label>
                        <select
                            id="ad_account_id"
                            name="ad_account_id"
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">{{ __('Select an Ad Account') }}</option>
                            @foreach ($assets['ad_accounts'] as $account)
                                <option
                                    value="{{ $account['id'] }}"
                                    @selected(($selected['ad_account_id'] ?? null) === $account['id'])
                                >
                                    {{ $account['name'] ?: $account['id'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="page_id" class="block text-xs font-medium uppercase tracking-wide text-slate-500">
                            {{ __('Facebook Page') }}
                        </label>
                        <select
                            id="page_id"
                            name="page_id"
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">{{ __('Select a Page') }}</option>
                            @foreach ($assets['pages'] as $page)
                                <option
                                    value="{{ $page['id'] }}"
                                    @selected(($selected['page_id'] ?? null) === $page['id'])
                                >
                                    {{ $page['name'] ?: $page['id'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="pixel_id" class="block text-xs font-medium uppercase tracking-wide text-slate-500">
                            {{ __('Pixel') }}
                        </label>
                        <select
                            id="pixel_id"
                            name="pixel_id"
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">{{ __('Select a Pixel') }}</option>
                            @foreach ($assets['pixels'] as $pixel)
                                <option
                                    value="{{ $pixel['id'] }}"
                                    @selected(($selected['pixel_id'] ?? null) === $pixel['id'])
                                >
                                    {{ $pixel['name'] ?: $pixel['id'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <p class="block text-xs font-medium uppercase tracking-wide text-slate-500">
                            {{ __('Lead Forms') }}
                        </p>
                        @if (empty($assets['lead_forms']))
                            <p class="mt-2 text-sm text-slate-500">{{ __('No lead forms were discovered for the available pages.') }}</p>
                        @else
                            <div class="mt-2 space-y-2 rounded-lg border border-slate-200 p-3">
                                @foreach ($assets['lead_forms'] as $form)
                                    <label class="flex items-start gap-2 text-sm text-slate-800">
                                        <input
                                            type="checkbox"
                                            name="lead_form_ids[]"
                                            value="{{ $form['id'] }}"
                                            class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                            @checked(in_array((string) $form['id'], $selectedLeadForms, true))
                                        >
                                        <span>
                                            <span class="font-medium">{{ $form['name'] ?: $form['id'] }}</span>
                                            @if (! empty($form['page_id']))
                                                <span class="block text-xs text-slate-500">{{ __('Page') }}: {{ $form['page_id'] }}</span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @endif

                    <div class="flex flex-wrap gap-2 pt-2">
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500"
                            @disabled(! $discoveryOk)
                        >
                            {{ __('Save selections') }}
                        </button>
                    </div>
                </form>
            </div>
        @endif

        @if ($supportsLeadFormSync && $integration['status'] !== 'disconnected')
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">{{ __('Lead Forms') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ __('Synchronize metadata for the selected lead forms. Lead submissions are not imported.') }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('integrations.lead-forms.sync', ['provider' => $integration['slug']]) }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500"
                            @disabled(empty($selectedLeadForms))
                        >
                            {{ __('Synchronize Forms') }}
                        </button>
                    </form>
                </div>

                @if (session('status_detail'))
                    <p class="mt-3 text-sm text-slate-600">{{ session('status_detail') }}</p>
                @endif

                @if (empty($selectedLeadForms))
                    <p class="mt-4 text-sm text-slate-500">
                        {{ __('Select one or more lead forms above, save selections, then synchronize.') }}
                    </p>
                @elseif ($leadForms->isEmpty())
                    <p class="mt-4 text-sm text-slate-500">
                        {{ __('No forms in the local catalog yet. Run Synchronize Forms to pull metadata.') }}
                    </p>
                @else
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                                    <th class="py-2 pr-4">{{ __('Form Name') }}</th>
                                    <th class="py-2 pr-4">{{ __('Status') }}</th>
                                    <th class="py-2 pr-4">{{ __('Locale') }}</th>
                                    <th class="py-2 pr-4">{{ __('Questions') }}</th>
                                    <th class="py-2">{{ __('Last Synced') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($leadForms as $form)
                                    <tr>
                                        <td class="py-3 pr-4 font-medium text-slate-900">
                                            {{ $form->name ?: $form->external_form_id }}
                                        </td>
                                        <td class="py-3 pr-4 text-slate-700">
                                            @if ($form->isActive())
                                                {{ $form->providerStatus() ?: __('Active') }}
                                            @else
                                                {{ __('Inactive') }}
                                            @endif
                                        </td>
                                        <td class="py-3 pr-4 text-slate-700">{{ $form->locale ?: '—' }}</td>
                                        <td class="py-3 pr-4 text-slate-700">{{ $form->questionCount() }}</td>
                                        <td class="py-3 text-slate-700">
                                            {{ $form->last_synced_at?->toDayDateTimeString() ?: '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif

        @if (($supportsWebhooks ?? false) && $integration['status'] !== 'disconnected')
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">{{ __('Webhook Status') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ __('Inbound Meta webhook deliveries are verified, recorded, and processed into leads through the shared import pipeline.') }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('integrations.webhooks.process', ['provider' => $integration['slug']]) }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500"
                            @disabled(($webhookStatus['pending_count'] ?? 0) === 0)
                        >
                            {{ __('Process Webhook Events') }}
                            @if (($webhookStatus['pending_count'] ?? 0) > 0)
                                ({{ $webhookStatus['pending_count'] }})
                            @endif
                        </button>
                    </form>
                </div>

                <dl class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Status') }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">
                            @php
                                $webhookLabel = match ($webhookStatus['status'] ?? 'awaiting') {
                                    'receiving' => __('Receiving'),
                                    'verified' => __('Verified'),
                                    'unsupported' => __('Unsupported'),
                                    default => __('Awaiting traffic'),
                                };
                            @endphp
                            {{ $webhookLabel }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Last webhook received') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            {{ ! empty($webhookStatus['last_received_at'])
                                ? \Illuminate\Support\Carbon::parse($webhookStatus['last_received_at'])->toDayDateTimeString()
                                : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Last verification') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            {{ ! empty($webhookStatus['last_verified_at'])
                                ? \Illuminate\Support\Carbon::parse($webhookStatus['last_verified_at'])->toDayDateTimeString()
                                : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Last webhook processed') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            {{ ! empty($webhookStatus['last_processed_at'])
                                ? \Illuminate\Support\Carbon::parse($webhookStatus['last_processed_at'])->toDayDateTimeString()
                                : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Last processing result') }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">
                            @php
                                $resultLabel = match ($webhookStatus['last_processing_result'] ?? null) {
                                    'processed' => __('Processed'),
                                    'failed' => __('Failed'),
                                    'ignored' => __('Ignored'),
                                    default => '—',
                                };
                            @endphp
                            {{ $resultLabel }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Processed / Failed') }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">
                            {{ $webhookStatus['processed_count'] ?? 0 }} / {{ $webhookStatus['failed_count'] ?? 0 }}
                        </dd>
                    </div>
                </dl>
            </div>
        @endif

        @if (($supportsLeadImport ?? false) && $integration['status'] !== 'disconnected')
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">{{ __('Lead Import') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ __('Manually import submissions from selected lead forms. Duplicates are skipped. No webhooks or polling.') }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('integrations.leads.import', ['provider' => $integration['slug']]) }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500"
                            @disabled(empty($selectedLeadForms))
                        >
                            {{ __('Import Leads') }}
                        </button>
                    </form>
                </div>

                @if ($lastLeadImport)
                    <dl class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Last import') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900">
                                {{ $lastLeadImport->imported_at?->toDayDateTimeString() ?: '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Imported') }}</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $lastLeadImport->imported_count }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Skipped') }}</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $lastLeadImport->skipped_count }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Failed') }}</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $lastLeadImport->failed_count }}</dd>
                        </div>
                    </dl>
                    @if ($lastLeadImport->message)
                        <p class="mt-3 text-sm text-slate-600">{{ $lastLeadImport->message }}</p>
                    @endif
                @else
                    <p class="mt-4 text-sm text-slate-500">
                        {{ __('No imports yet. Select forms, save selections, then run Import Leads.') }}
                    </p>
                @endif
            </div>
        @endif

        @if (($supportsOfflineConversions ?? false) && $integration['status'] !== 'disconnected')
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">{{ __('Offline Conversions') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ __('Manually upload CRM conversion events to the provider. Already uploaded events are skipped.') }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('integrations.conversions.upload', ['provider' => $integration['slug']]) }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500"
                            @disabled(
                                empty($selected['pixel_id'] ?? null)
                                && (
                                    empty($selected['customer_id'] ?? null)
                                    || empty($selectedConversionActions)
                                )
                            )
                        >
                            {{ __('Upload Conversions') }}
                        </button>
                    </form>
                </div>

                @if ($lastConversionUpload)
                    <dl class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Last upload') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900">
                                {{ $lastConversionUpload->finished_at?->toDayDateTimeString()
                                    ?: ($lastConversionUpload->started_at?->toDayDateTimeString() ?: '—') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Uploaded') }}</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-900">
                                {{ $lastConversionUpload->metadata['uploaded'] ?? $lastConversionUpload->records_succeeded }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Failed') }}</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-900">
                                {{ $lastConversionUpload->metadata['failed'] ?? $lastConversionUpload->records_failed }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Status') }}</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ __(ucfirst($lastConversionUpload->status)) }}</dd>
                        </div>
                    </dl>
                    @if ($lastConversionUpload->message)
                        <p class="mt-3 text-sm text-slate-600">{{ $lastConversionUpload->message }}</p>
                    @endif
                @else
                    <p class="mt-4 text-sm text-slate-500">
                        @if (! empty($selected['pixel_id'] ?? null))
                            {{ __('No conversion uploads yet. Select a Pixel above, then run Upload Conversions.') }}
                        @elseif (! empty($selected['customer_id'] ?? null) && ! empty($selectedConversionActions))
                            {{ __('No conversion uploads yet. Save your customer and conversion action selections above, then run Upload Conversions.') }}
                        @elseif ($hasGoogleAssets ?? false)
                            {{ __('No conversion uploads yet. Select a Customer Account and Conversion Actions above, then run Upload Conversions.') }}
                        @else
                            {{ __('No conversion uploads yet. Configure provider assets above, then run Upload Conversions.') }}
                        @endif
                    </p>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
