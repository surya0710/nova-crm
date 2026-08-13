<x-app-layout>
    <x-flash-messages />

    <x-layouts.dashboard :title="__('Payroll')" :subtitle="__('Salary processing and finance operations')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="md:col-span-2 xl:col-span-3">
            <x-ui.card>
                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-ink-heading">
                            {{ __('Attendance window for payroll calculation') }}
                        </h2>
                        <p class="mt-1 text-xs text-ink-muted">
                            {{ __('Payroll uses attendance from the payroll periods you define here. Set the date range before locking attendance and running payroll.') }}
                        </p>
                        @if ($latestPeriod)
                            <p class="mt-2 text-xs text-ink-muted">
                                {{ __('Latest period: :name (:start – :end, :status)', [
                                    'name' => $latestPeriod->name,
                                    'start' => $latestPeriod->start_date?->toDateString(),
                                    'end' => $latestPeriod->end_date?->toDateString(),
                                    'status' => $periodStatusLabels[$latestPeriod->status] ?? $latestPeriod->status,
                                ]) }}
                            </p>
                        @else
                            <p class="mt-2 text-xs text-ink-muted">
                                {{ __('No payroll periods defined yet. Create one using the form on the right.') }}
                            </p>
                        @endif
                        @can('viewAny', \App\Models\PayrollPeriod::class)
                            <div class="mt-3">
                                <x-ui.button :href="route('hrms.payroll.periods.index')" variant="link" size="xs">
                                    {{ __('Open full period management') }}
                                </x-ui.button>
                            </div>
                        @endcan
                    </div>
                    @can('create', \App\Models\PayrollPeriod::class)
                        <form method="POST" action="{{ route('hrms.payroll.periods.store') }}" class="mt-3 w-full max-w-md space-y-3 md:mt-0">
                            @csrf
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <x-forms.field :label="__('Name')" name="name">
                                    <x-forms.input
                                        name="name"
                                        placeholder="{{ __('Name') }}"
                                        :value="old('name', now()->format('F Y'))"
                                        required
                                    />
                                </x-forms.field>
                                <x-forms.field :label="__('Start Date')" name="start_date">
                                    <x-forms.input
                                        name="start_date"
                                        type="date"
                                        :value="old('start_date', now()->startOfMonth()->toDateString())"
                                        required
                                    />
                                </x-forms.field>
                                <x-forms.field :label="__('End Date')" name="end_date">
                                    <x-forms.input
                                        name="end_date"
                                        type="date"
                                        :value="old('end_date', now()->endOfMonth()->toDateString())"
                                        required
                                    />
                                </x-forms.field>
                            </div>
                            <div class="flex justify-end">
                                <x-ui.button type="submit" variant="primary" size="sm">
                                    {{ __('Save attendance period') }}
                                </x-ui.button>
                            </div>
                        </form>
                    @endcan
                </div>
            </x-ui.card>
        </div>

        <x-slot:kpis>
            <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('hrms.payroll.runs.index') }}" class="rounded-xl border border-line bg-surface-card p-4 shadow-sm transition hover:border-primary-300">
                    <p class="text-sm text-ink-muted">{{ __('Pending Payroll') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-ink-heading">{{ $enterprise['pending_payroll'] ?? 0 }}</p>
                </a>
                <a href="{{ route('hrms.payroll.runs.index') }}" class="rounded-xl border border-line bg-surface-card p-4 shadow-sm transition hover:border-primary-300">
                    <p class="text-sm text-ink-muted">{{ __('Generated Payroll') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-ink-heading">{{ $enterprise['generated_payroll'] ?? 0 }}</p>
                </a>
                <a href="{{ route('hrms.payroll.runs.index') }}" class="rounded-xl border border-line bg-surface-card p-4 shadow-sm transition hover:border-primary-300">
                    <p class="text-sm text-ink-muted">{{ __('Paid Payroll') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-ink-heading">{{ $enterprise['paid_payroll'] ?? 0 }}</p>
                </a>
                <div class="rounded-xl border border-line bg-surface-card p-4 shadow-sm">
                    <p class="text-sm text-ink-muted">{{ __('Upcoming Salary Date') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-ink-heading">{{ $enterprise['upcoming_salary_date'] ?? '—' }}</p>
                </div>
                <a href="{{ route('hrms.payroll.assignments.index') }}" class="rounded-xl border border-line bg-surface-card p-4 shadow-sm transition hover:border-primary-300">
                    <p class="text-sm text-ink-muted">{{ __('Missing Salary Structure') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-ink-heading">{{ $enterprise['missing_salary_structure'] ?? 0 }}</p>
                </a>
                <div class="rounded-xl border border-line bg-surface-card p-4 shadow-sm">
                    <p class="text-sm text-ink-muted">{{ __('Payroll Health') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-ink-heading">{{ ucfirst($enterprise['payroll_health']['status'] ?? '—') }}</p>
                    <p class="text-xs text-ink-muted">{{ __('Score') }}: {{ $enterprise['payroll_health']['score'] ?? 0 }}</p>
                </div>
                <a href="{{ route('hrms.payroll.components.index') }}" class="rounded-xl border border-line bg-surface-card p-4 shadow-sm transition hover:border-primary-300">
                    <p class="text-sm text-ink-muted">{{ __('Components') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-ink-heading">{{ $componentCount }}</p>
                </a>
                <a href="{{ route('hrms.payroll.structures.index') }}" class="rounded-xl border border-line bg-surface-card p-4 shadow-sm transition hover:border-primary-300">
                    <p class="text-sm text-ink-muted">{{ __('Structures') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-ink-heading">{{ $structureCount }}</p>
                </a>
            </div>
        </x-slot:kpis>

        <div class="md:col-span-2 xl:col-span-3">
            <x-entity.section :title="__('Recent Payroll Runs')">
                <x-slot:actions>
                    @can('create', \App\Models\PayrollRun::class)
                        <x-ui.button :href="route('hrms.payroll.runs.index')" variant="link" size="sm">{{ __('Manage runs') }}</x-ui.button>
                    @endcan
                </x-slot:actions>
                @if ($latestRuns->isEmpty())
                    <x-ui.empty-state-preset variant="payroll" />
                @else
                    @php
                        $density = $shellNav['density'] ?? 'comfortable';
                        $runColumns = [__('Period'), __('Status'), __('Success'), __('Errors')];
                    @endphp
                    <x-tables.table :columns="$runColumns" :dense="$density === 'compact'">
                        @foreach ($latestRuns as $run)
                            <tr class="hover:bg-surface-muted/60 transition">
                                <td class="px-4 py-3">
                                    <a href="{{ route('hrms.payroll.runs.show', $run) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ $run->period?->name }}</a>
                                </td>
                                <td class="px-4 py-3">
                                    <x-ui.badge variant="neutral">{{ config('hrms.payroll.run_statuses.'.$run->status, $run->status) }}</x-ui.badge>
                                </td>
                                <td class="px-4 py-3 text-sm text-ink-muted">{{ $run->success_count }}</td>
                                <td class="px-4 py-3 text-sm text-ink-muted">{{ $run->error_count }}</td>
                            </tr>
                        @endforeach
                    </x-tables.table>
                @endif
            </x-entity.section>
        </div>

        <div class="md:col-span-2 xl:col-span-3">
            <x-ui.card>
                <p class="text-sm text-ink-muted">{{ __('Payroll finance covers ledger generation, bank exports, loans, advances, reimbursements, final settlements, and finance reports.') }}</p>
            </x-ui.card>
        </div>

        @can('viewAny', \App\Models\PayrollLedgerEntry::class)
            <div class="md:col-span-2 xl:col-span-3">
                <div class="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-8">
                    @foreach ([
                        ['route' => 'hrms.payroll.ledger.index', 'label' => __('Ledger')],
                        ['route' => 'hrms.payroll.journals.index', 'label' => __('Journals')],
                        ['route' => 'hrms.payroll.bank-exports.index', 'label' => __('Bank Exports')],
                        ['route' => 'hrms.payroll.loans.index', 'label' => __('Loans')],
                        ['route' => 'hrms.payroll.advances.index', 'label' => __('Advances')],
                        ['route' => 'hrms.payroll.reimbursements.index', 'label' => __('Reimbursements')],
                        ['route' => 'hrms.payroll.settlements.index', 'label' => __('Settlements')],
                        ['route' => 'hrms.payroll.reports.index', 'label' => __('Reports')],
                    ] as $link)
                        <a href="{{ route($link['route']) }}" class="rounded-xl border border-line bg-surface-card p-3 text-center shadow-sm transition hover:border-primary-300">
                            <p class="text-xs text-ink-muted">{{ $link['label'] }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endcan
    </x-layouts.dashboard>
</x-app-layout>
