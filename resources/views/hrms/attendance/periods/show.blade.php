@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <x-ui.page-header :title="$period->name">
            <x-slot:actions>
                <x-ui.button :href="route('hrms.attendance.periods.index')" variant="secondary" size="sm">{{ __('Back') }}</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card>
            <dl class="grid grid-cols-1 gap-3 md:grid-cols-3 text-sm">
                <div>
                    <dt class="text-ink-muted">{{ __('Range') }}</dt>
                    <dd>{{ $period->start_date->toDateString() }} → {{ $period->end_date->toDateString() }}</dd>
                </div>
                <div>
                    <dt class="text-ink-muted">{{ __('Status') }}</dt>
                    <dd>{{ $period->statusLabel() }}</dd>
                </div>
                <div>
                    <dt class="text-ink-muted">{{ __('Payroll Period') }}</dt>
                    <dd>{{ $period->payrollPeriod?->name ?? '—' }}</dd>
                </div>
            </dl>

            @can('lock', $period)
                <div class="mt-4 flex flex-wrap gap-2">
                    @if ($period->isOpen())
                        <form method="POST" action="{{ route('hrms.attendance.periods.freeze', $period) }}">
                            @csrf
                            <x-ui.button type="submit" size="sm" variant="secondary">{{ __('Freeze') }}</x-ui.button>
                        </form>
                        <form method="POST" action="{{ route('hrms.attendance.periods.lock', $period) }}">
                            @csrf
                            <x-ui.button type="submit" size="sm">{{ __('Lock & Snapshot') }}</x-ui.button>
                        </form>
                    @endif
                    @if ($period->isFrozen())
                        <form method="POST" action="{{ route('hrms.attendance.periods.lock', $period) }}">
                            @csrf
                            <x-ui.button type="submit" size="sm">{{ __('Lock & Snapshot') }}</x-ui.button>
                        </form>
                        <form method="POST" action="{{ route('hrms.attendance.periods.reopen', $period) }}">
                            @csrf
                            <x-ui.button type="submit" size="sm" variant="secondary">{{ __('Reopen') }}</x-ui.button>
                        </form>
                    @endif
                    @if ($period->isLocked())
                        <form method="POST" action="{{ route('hrms.attendance.periods.reopen', $period) }}">
                            @csrf
                            <x-ui.button type="submit" size="sm" variant="secondary">{{ __('Reopen') }}</x-ui.button>
                        </form>
                    @endif
                    <x-ui.button :href="route('hrms.attendance.periods.validate', $period)" variant="secondary" size="sm">{{ __('Validate') }}</x-ui.button>
                </div>
            @endcan
        </x-ui.card>

        <x-ui.card>
            <h2 class="mb-3 text-base font-semibold">{{ __('Validation') }}</h2>
            @if ($validation['passed'])
                <p class="text-sm text-ink-muted">{{ __('No blocking validation errors.') }}</p>
            @else
                <ul class="list-disc space-y-1 pl-5 text-sm">
                    @foreach ($validation['errors'] as $error)
                        <li><code>{{ $error['code'] }}</code> — {{ $error['message'] }}</li>
                    @endforeach
                </ul>
            @endif
            @if (! empty($validation['warnings']))
                <h3 class="mt-4 mb-2 text-sm font-medium">{{ __('Warnings') }}</h3>
                <ul class="list-disc space-y-1 pl-5 text-sm text-ink-muted">
                    @foreach ($validation['warnings'] as $warning)
                        <li><code>{{ $warning['code'] }}</code> — {{ $warning['message'] }}</li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        <x-ui.card>
            <h2 class="mb-3 text-base font-semibold">{{ __('Snapshots') }}</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-edge text-left text-ink-muted">
                            <th class="px-3 py-2">{{ __('Version') }}</th>
                            <th class="px-3 py-2">{{ __('Status') }}</th>
                            <th class="px-3 py-2">{{ __('Records') }}</th>
                            <th class="px-3 py-2">{{ __('Hash') }}</th>
                            <th class="px-3 py-2">{{ __('Generated') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($period->snapshots->sortByDesc('snapshot_version') as $snapshot)
                            <tr class="border-b border-edge/60">
                                <td class="px-3 py-2">V{{ $snapshot->snapshot_version }}</td>
                                <td class="px-3 py-2">{{ $snapshot->status }}</td>
                                <td class="px-3 py-2">{{ $snapshot->record_count }}</td>
                                <td class="px-3 py-2 font-mono text-xs">{{ \Illuminate\Support\Str::limit($snapshot->payload_hash, 16) }}</td>
                                <td class="px-3 py-2">{{ $snapshot->generated_at?->toDateTimeString() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-ink-muted">{{ __('No snapshots generated yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
@endsection
