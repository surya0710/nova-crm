@php
    $density = $shellNav['density'] ?? 'comfortable';
    $resultColumns = [__('Employee'), __('Gross'), __('Deductions'), __('Net'), __('Payable Days'), __('Hash')];
    $errorColumns = [__('Employee'), __('Code'), __('Message')];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="__('Payroll Run')"
        :subtitle="$run->period?->name"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Runs'), 'href' => route('hrms.payroll.runs.index')],
                ['label' => $run->period?->name, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('hrms.payroll.runs.index')" variant="secondary" size="sm">{{ __('Back') }}</x-ui.button>
        </x-slot:actions>

        <x-slot:tabs>
            <x-ui.badge variant="neutral">{{ config('hrms.payroll.run_statuses.'.$run->status, $run->status) }}</x-ui.badge>
        </x-slot:tabs>

        <x-entity.section :title="__('Run Summary')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Status')">{{ config('hrms.payroll.run_statuses.'.$run->status, $run->status) }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Employees')">{{ $run->employee_count }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Success')">{{ $run->success_count }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Errors')">{{ $run->error_count }}</x-entity.definition-item>
            </x-entity.definition-list>

            <div class="mt-4 flex flex-wrap gap-3">
                @can('calculate', $run)
                    @if ($run->status === 'draft')
                        <form method="POST" action="{{ route('hrms.payroll.runs.calculate', $run) }}">@csrf
                            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Calculate') }}</x-ui.button>
                        </form>
                    @elseif ($run->status === 'running')
                        <form method="POST" action="{{ route('hrms.payroll.runs.recalculate', $run) }}">@csrf
                            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Recalculate') }}</x-ui.button>
                        </form>
                    @endif
                @endcan

                @can('approve', $run)
                    @if ($run->canApprove())
                        <form method="POST" action="{{ route('hrms.payroll.runs.approve', $run) }}" class="flex flex-wrap items-end gap-2">
                            @csrf
                            <input type="hidden" name="approval_type" value="hr">
                            <x-forms.input type="text" name="notes" placeholder="{{ __('Approval notes (optional)') }}" />
                            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Approve') }}</x-ui.button>
                        </form>
                    @endif
                @endcan

                @can('publish', $run)
                    @if ($run->canPublish())
                        <form method="POST" action="{{ route('hrms.payroll.runs.publish', $run) }}" class="flex flex-wrap items-center gap-2">
                            @csrf
                            <label class="inline-flex items-center gap-2 text-sm text-ink-heading">
                                <input type="checkbox" name="send_emails" value="1" checked class="rounded border-line">
                                {{ __('Queue payslip emails') }}
                            </label>
                            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Publish') }}</x-ui.button>
                        </form>
                    @endif
                @endcan

                @if ($run->isPublished())
                    <x-ui.button :href="route('hrms.payroll.payslips.index', ['payroll_run_id' => $run->id])" variant="secondary" size="sm">{{ __('View Payslips') }}</x-ui.button>
                    @can('viewAny', \App\Models\PayrollLedgerEntry::class)
                        <x-ui.button :href="route('hrms.payroll.ledger.index', ['payroll_run_id' => $run->id])" variant="secondary" size="sm">{{ __('Ledger') }}</x-ui.button>
                    @endcan
                    @can('viewAny', \App\Models\PayrollBankExport::class)
                        <x-ui.button :href="route('hrms.payroll.bank-exports.index', ['payroll_run_id' => $run->id])" variant="secondary" size="sm">{{ __('Bank Export') }}</x-ui.button>
                    @endcan
                @endif

                @can('reverse', $run)
                    @if ($run->canReverse())
                        <form method="POST" action="{{ route('hrms.payroll.runs.reverse', $run) }}" class="flex flex-wrap items-end gap-2">
                            @csrf
                            <x-forms.input type="text" name="reason" required minlength="3" placeholder="{{ __('Reversal reason (required)') }}" />
                            <x-ui.button type="submit" variant="danger" size="sm">{{ __('Reverse Payroll') }}</x-ui.button>
                        </form>
                    @endif
                @endcan
            </div>
            @error('run') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
            @error('period') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
            @error('reason') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
        </x-entity.section>

        @if ($approvals->isNotEmpty() || $publication)
            <x-entity.section :title="__('Approval & Publication')">
                <div class="space-y-2 text-sm text-ink-muted">
                    @foreach ($approvals as $approval)
                        <p>
                            {{ __('Approved (:type) by :name on :date', [
                                'type' => config('hrms.payroll.approval_types.'.$approval->approval_type, $approval->approval_type),
                                'name' => $approval->approvedBy?->name ?? '—',
                                'date' => $approval->approved_at?->toDateTimeString(),
                            ]) }}
                            @if ($approval->notes)
                                — {{ $approval->notes }}
                            @endif
                        </p>
                    @endforeach
                    @if ($publication)
                        <p>
                            {{ __('Published by :name on :date — :count payslip(s), :emails email(s) queued', [
                                'name' => $publication->publishedBy?->name ?? '—',
                                'date' => $publication->published_at?->toDateTimeString(),
                                'count' => $publication->payslip_count,
                                'emails' => $publication->email_queued_count,
                            ]) }}
                        </p>
                    @endif
                </div>
            </x-entity.section>
        @endif

        <x-entity.section :title="__('Results')">
            @if ($run->results->isEmpty())
                <p class="text-sm text-ink-muted">{{ __('No results yet. Run calculation to generate results.') }}</p>
            @else
                <x-tables.table :columns="$resultColumns" :dense="$density === 'compact'">
                    @foreach ($run->results as $result)
                        <tr class="hover:bg-surface-muted/60 transition">
                            <td class="px-4 py-3">
                                <a href="{{ route('hrms.payroll.results.show', $result) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ $result->employee?->full_name }}</a>
                            </td>
                            <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $result->gross_salary, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $result->total_deductions, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-ink-muted">{{ number_format((float) $result->net_salary, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-ink-muted">{{ $result->payable_days }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-ink-muted">{{ \Illuminate\Support\Str::limit($result->calculation_hash, 12) }}</td>
                        </tr>
                    @endforeach
                </x-tables.table>
            @endif
        </x-entity.section>

        <x-entity.section :title="__('Validation Errors')">
            @if ($run->validationErrors->isEmpty())
                <p class="text-sm text-ink-muted">{{ __('No validation errors.') }}</p>
            @else
                <x-tables.table :columns="$errorColumns" :dense="$density === 'compact'">
                    @foreach ($run->validationErrors as $error)
                        <tr class="hover:bg-surface-muted/60 transition">
                            <td class="px-4 py-3 text-sm text-ink-heading">{{ $error->employee?->full_name ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-ink-muted">{{ $error->code }}</td>
                            <td class="px-4 py-3 text-sm text-ink-muted">{{ $error->message }}</td>
                        </tr>
                    @endforeach
                </x-tables.table>
            @endif
        </x-entity.section>
    </x-layouts.entity-detail>
</x-app-layout>
