<x-platform-layout>
    <x-flash-messages />

    <div class="mx-auto max-w-6xl space-y-6">
        <x-nav.breadcrumbs :items="[
            ['label' => __('Platform'), 'href' => route('platform.dashboard')],
            ['label' => __('Onboarding'), 'href' => route('platform.onboarding.index')],
            ['label' => $onboarding->organization?->name ?? __('Wizard #:id', ['id' => $onboarding->id]), 'current' => true],
        ]" />

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-ink-heading">{{ __('Onboarding Wizard') }}</h1>
                <p class="mt-1 text-sm text-ink-muted">
                    {{ $stepMeta['label'] ?? $step }} · {{ $progress['progress_percent'] }}% {{ __('complete') }}
                    @if ($onboarding->organization)
                        · <a class="text-primary-700 hover:underline" href="{{ route('platform.organizations.show', $onboarding->organization) }}">{{ $onboarding->organization->name }}</a>
                    @endif
                </p>
            </div>
            <x-ui.badge :variant="$onboarding->status === 'completed' ? 'success' : ($onboarding->status === 'failed' ? 'danger' : 'warning')">
                {{ ucfirst(str_replace('_', ' ', $onboarding->status)) }}
            </x-ui.badge>
        </div>

        <div class="h-2 overflow-hidden rounded-full bg-surface-muted">
            <div class="h-full bg-primary-600 transition-all" style="width: {{ $progress['progress_percent'] }}%"></div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[240px_1fr]">
            <aside class="space-y-2">
                @foreach ($progress['steps'] as $item)
                    <div @class([
                        'rounded-lg border px-3 py-2 text-sm',
                        'border-primary-400 bg-primary-50/50' => $item['current'],
                        'border-line' => ! $item['current'],
                    ])>
                        <p class="font-medium text-ink-heading">{{ $item['label'] }}</p>
                        <p class="text-xs text-ink-muted">
                            @if ($item['completed']) {{ __('Completed') }}
                            @elseif ($item['skipped']) {{ __('Skipped') }}
                            @elseif ($item['current']) {{ __('Current') }}
                            @else {{ __('Pending') }}
                            @endif
                        </p>
                    </div>
                @endforeach
            </aside>

            <section class="rounded-xl border border-line bg-surface-card p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-ink-heading">{{ $stepMeta['label'] ?? $step }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ $stepMeta['description'] ?? '' }}</p>

                @if ($onboarding->last_error)
                    <x-ui.alert variant="danger" class="mt-4">{{ $onboarding->last_error }}</x-ui.alert>
                @endif

                @if ($onboarding->isTerminal())
                    <x-ui.alert variant="info" class="mt-4">{{ __('This onboarding session is closed.') }}</x-ui.alert>
                    <ul class="mt-4 space-y-2 text-sm">
                        @foreach ($progress['checklist'] as $check)
                            <li class="flex items-center gap-2">
                                <span>{{ $check['passed'] ? '✓' : '○' }}</span>
                                <span>{{ $check['label'] }}</span>
                                @if (! empty($check['warning']))
                                    <span class="text-xs text-ink-muted">({{ $check['warning'] }})</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    @include('platform.onboarding.steps.'.$step, [
                        'onboarding' => $onboarding,
                        'stepData' => $stepData,
                        'allStepData' => $allStepData,
                    ])
                @endif
            </section>
        </div>
    </div>
</x-platform-layout>
