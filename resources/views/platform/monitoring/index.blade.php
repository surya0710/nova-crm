@php
    $healthVariant = fn ($status) => match ($status) {
        'healthy', 'ok' => 'success',
        'degraded', 'busy', 'partial' => 'warning',
        'unhealthy', 'critical', 'missing' => 'danger',
        default => 'neutral',
    };
@endphp

<x-platform-layout>
    <x-layouts.dashboard
        :title="__('System Monitoring')"
        :subtitle="__('Queue, infrastructure, logs, and platform health')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Monitoring'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:kpis>
            <x-ui.stat-card :label="__('System Health')" :value="ucfirst($snapshot['system']['status'] ?? 'unknown')" />
            <x-ui.stat-card :label="__('Queue Pending')" :value="number_format($snapshot['queue']['pending'] ?? 0)" />
            <x-ui.stat-card :label="__('Failed Jobs')" :value="number_format($snapshot['queue']['failed'] ?? 0)" />
            <x-ui.stat-card :label="__('Storage Used')" :value="number_format($snapshot['storage']['used_mb'] ?? 0, 1) . ' MB'" />
        </x-slot:kpis>

        <x-ui.card>
            <x-slot:header><h2 class="text-sm font-semibold text-ink-heading">{{ __('Queue') }}</h2></x-slot:header>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-ink-muted">{{ __('Driver') }}</dt><dd class="font-medium text-ink">{{ $snapshot['queue']['driver'] ?? '—' }}</dd></div>
                <div><dt class="text-ink-muted">{{ __('Status') }}</dt><dd><x-ui.badge :variant="$healthVariant($snapshot['queue']['status'] ?? 'unknown')">{{ ucfirst($snapshot['queue']['status'] ?? 'unknown') }}</x-ui.badge></dd></div>
            </dl>
        </x-ui.card>

        <x-ui.card>
            <x-slot:header><h2 class="text-sm font-semibold text-ink-heading">{{ __('Cache') }}</h2></x-slot:header>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-ink-muted">{{ __('Driver') }}</dt><dd class="font-medium text-ink">{{ $snapshot['cache']['driver'] ?? '—' }}</dd></div>
                <div><dt class="text-ink-muted">{{ __('Status') }}</dt><dd><x-ui.badge :variant="$healthVariant($snapshot['cache']['status'] ?? 'unknown')">{{ ucfirst($snapshot['cache']['status'] ?? 'unknown') }}</x-ui.badge></dd></div>
            </dl>
        </x-ui.card>

        <x-ui.card>
            <x-slot:header><h2 class="text-sm font-semibold text-ink-heading">{{ __('Database') }}</h2></x-slot:header>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-ink-muted">{{ __('Driver') }}</dt><dd class="font-medium text-ink">{{ $snapshot['database']['driver'] ?? '—' }}</dd></div>
                <div><dt class="text-ink-muted">{{ __('Latency') }}</dt><dd class="font-medium text-ink">{{ ($snapshot['database']['latency_ms'] ?? '—') }} ms</dd></div>
            </dl>
        </x-ui.card>

        <x-ui.card>
            <x-slot:header><h2 class="text-sm font-semibold text-ink-heading">{{ __('Redis') }}</h2></x-slot:header>
            <p class="text-sm text-ink">{{ ucfirst($snapshot['redis']['status'] ?? 'unknown') }}</p>
            @if (! empty($snapshot['redis']['message']))
                <p class="mt-1 text-xs text-ink-muted">{{ $snapshot['redis']['message'] }}</p>
            @endif
        </x-ui.card>

        <x-ui.card>
            <x-slot:header><h2 class="text-sm font-semibold text-ink-heading">{{ __('Scheduler') }}</h2></x-slot:header>
            <p class="text-sm text-ink">{{ ucfirst($snapshot['scheduler']['status'] ?? 'unknown') }}</p>
            <p class="mt-1 text-xs text-ink-muted">{{ $snapshot['scheduler']['note'] ?? '' }}</p>
        </x-ui.card>

        <x-ui.card>
            <x-slot:header><h2 class="text-sm font-semibold text-ink-heading">{{ __('Storage') }}</h2></x-slot:header>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-ink-muted">{{ __('Disk') }}</dt><dd class="font-medium text-ink">{{ $snapshot['storage']['disk'] ?? '—' }}</dd></div>
                <div><dt class="text-ink-muted">{{ __('Used') }}</dt><dd class="font-medium text-ink">{{ number_format($snapshot['storage']['used_mb'] ?? 0, 2) }} MB</dd></div>
            </dl>
        </x-ui.card>

        <x-ui.card class="md:col-span-2 xl:col-span-3">
            <x-slot:header><h2 class="text-sm font-semibold text-ink-heading">{{ __('Failed Jobs') }}</h2></x-slot:header>
            @if (empty($snapshot['failed_jobs']))
                <p class="text-sm text-ink-muted">{{ __('No failed jobs.') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="bg-surface-muted/50 text-ink-muted">
                            <tr>
                                <th scope="col" class="px-3 py-2 text-left font-medium">{{ __('Queue') }}</th>
                                <th scope="col" class="px-3 py-2 text-left font-medium">{{ __('Exception') }}</th>
                                <th scope="col" class="px-3 py-2 text-left font-medium">{{ __('Failed At') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($snapshot['failed_jobs'] as $job)
                                <tr>
                                    <td class="px-3 py-2 text-ink">{{ $job['queue'] }}</td>
                                    <td class="px-3 py-2 text-xs text-ink-muted">{{ $job['exception'] }}</td>
                                    <td class="px-3 py-2 text-ink-muted">{{ $job['failed_at'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>

        <x-ui.card class="md:col-span-2 xl:col-span-3">
            <x-slot:header><h2 class="text-sm font-semibold text-ink-heading">{{ __('Recent Logs') }}</h2></x-slot:header>
            @if (empty($snapshot['logs']))
                <p class="text-sm text-ink-muted">{{ __('Log file not readable.') }}</p>
            @else
                <pre class="max-h-80 overflow-auto rounded-lg bg-surface-muted/40 p-4 text-xs text-ink-muted">{{ implode("\n", $snapshot['logs']) }}</pre>
            @endif
        </x-ui.card>

        <x-ui.card class="md:col-span-2 xl:col-span-3">
            <x-slot:header><h2 class="text-sm font-semibold text-ink-heading">{{ __('System Health') }}</h2></x-slot:header>
            <dl class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                <div><dt class="text-ink-muted">{{ __('PHP') }}</dt><dd class="font-medium text-ink">{{ $snapshot['system']['php_version'] ?? '—' }}</dd></div>
                <div><dt class="text-ink-muted">{{ __('Laravel') }}</dt><dd class="font-medium text-ink">{{ $snapshot['system']['laravel_version'] ?? '—' }}</dd></div>
                <div><dt class="text-ink-muted">{{ __('Environment') }}</dt><dd class="font-medium text-ink">{{ $snapshot['system']['app_env'] ?? '—' }}</dd></div>
                <div><dt class="text-ink-muted">{{ __('Checked At') }}</dt><dd class="font-medium text-ink">{{ isset($snapshot['system']['checked_at']) ? \Illuminate\Support\Carbon::parse($snapshot['system']['checked_at'])->format('M j, Y H:i') : '—' }}</dd></div>
            </dl>
        </x-ui.card>
    </x-layouts.dashboard>
</x-platform-layout>
