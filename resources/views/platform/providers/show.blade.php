@php
    $canManage = auth('platform')->user()->hasPermission('platform.providers.manage');
    $statusVariant = match ($provider['status']) {
        'configured' => 'success',
        'partial' => 'warning',
        'missing' => 'danger',
        default => 'neutral',
    };
@endphp

<x-platform-layout>
    <x-layouts.entity-detail
        :title="$provider['label']"
        :subtitle="__('Provider credential inspection')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Providers'), 'href' => route('platform.providers.index')],
                ['label' => $provider['label'], 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('platform.providers.index')" variant="ghost" size="sm">{{ __('Back to providers') }}</x-ui.button>
        </x-slot:actions>

        <x-entity.section :title="__('Status')">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium text-ink-muted">{{ __('Category') }}</dt>
                    <dd class="mt-1 text-sm text-ink">{{ ucfirst($provider['category'] ?? 'other') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-ink-muted">{{ __('Configuration') }}</dt>
                    <dd class="mt-1">
                        <x-ui.badge :variant="$statusVariant">{{ ucfirst($provider['status']) }}</x-ui.badge>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-ink-muted">{{ __('Configured Keys') }}</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $provider['configured_keys'] }} / {{ $provider['total_keys'] }}</dd>
                </div>
            </dl>
        </x-entity.section>

        <x-entity.section :title="__('Environment Keys')">
            @if (empty($provider['env_keys']))
                <p class="text-sm text-ink-muted">{{ __('No environment keys registered for this provider.') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="bg-surface-muted/50 text-ink-muted">
                            <tr>
                                <th scope="col" class="px-3 py-2 text-left font-medium">{{ __('Key') }}</th>
                                <th scope="col" class="px-3 py-2 text-left font-medium">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($provider['env_keys'] as $envKey)
                                @php $isConfigured = ! in_array($envKey, $provider['missing_keys'] ?? [], true); @endphp
                                <tr>
                                    <td class="px-3 py-2 font-mono text-xs text-ink">{{ $envKey }}</td>
                                    <td class="px-3 py-2">
                                        <x-ui.badge :variant="$isConfigured ? 'success' : 'danger'">
                                            {{ $isConfigured ? __('Configured') : __('Missing') }}
                                        </x-ui.badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-entity.section>

        @if ($canManage)
            <x-slot:aside>
                <x-entity.section :title="__('Actions')">
                    <div class="space-y-2" x-data="{ message: '' }">
                        <x-ui.button type="button" variant="secondary" class="w-full"
                            @click="fetch(@js(route('platform.providers.validate', $provider['key'])), { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } }).then(r => r.json()).then(d => message = d.message || 'Done')">
                            {{ __('Validate Credentials') }}
                        </x-ui.button>
                        <x-ui.button type="button" variant="primary" class="w-full"
                            @click="fetch(@js(route('platform.providers.test', $provider['key'])), { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } }).then(r => r.json()).then(d => message = d.message || 'Done')">
                            {{ __('Run Test') }}
                        </x-ui.button>
                        <p x-show="message" x-text="message" class="text-xs text-ink-muted"></p>
                        <p class="text-xs text-ink-muted">{{ __('Tests verify credential presence only; no outbound network calls are made.') }}</p>
                    </div>
                </x-entity.section>
            </x-slot:aside>
        @endif
    </x-layouts.entity-detail>
</x-platform-layout>
