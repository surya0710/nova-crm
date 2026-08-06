<?php

namespace App\Services\Hrms;

use App\Events\EmployeeLoanClosed;
use App\Events\EmployeeLoanCreated;
use App\Events\EmployeeSettlementCompleted;
use App\Events\PayrollBankExported;
use App\Events\PayrollLedgerGenerated;
use App\Events\PayrollReversed;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanRecovery;
use App\Models\EmployeeSettlement;
use App\Models\ExpenseReimbursement;
use App\Models\LeaveBalance;
use App\Models\PayrollBankExport;
use App\Models\PayrollJournal;
use App\Models\PayrollJournalLine;
use App\Models\PayrollLedgerEntry;
use App\Models\PayrollResult;
use App\Models\PayrollReversal;
use App\Models\PayrollRun;
use App\Models\SalaryAdvance;
use App\Models\SalaryAdvanceRecovery;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PayrollFinanceService
{
    public const ENGINE_VERSION = '10.3.5';

    /** @var array<string, array{code: string, name: string}> */
    public const ACCOUNT_MAP = [
        'salary_expense' => ['code' => 'SAL_EXP', 'name' => 'Salary Expense'],
        'salary_payable' => ['code' => 'SAL_PAY', 'name' => 'Salary Payable'],
        'pf_payable' => ['code' => 'PF_PAY', 'name' => 'PF Payable'],
        'esi_payable' => ['code' => 'ESI_PAY', 'name' => 'ESI Payable'],
        'pt_payable' => ['code' => 'PT_PAY', 'name' => 'Professional Tax Payable'],
        'tds_payable' => ['code' => 'TDS_PAY', 'name' => 'TDS Payable'],
        'employer_pf_expense' => ['code' => 'PF_ER_EXP', 'name' => 'Employer PF Expense'],
        'employer_esi_expense' => ['code' => 'ESI_ER_EXP', 'name' => 'Employer ESI Expense'],
        'other_deduction_payable' => ['code' => 'OTH_DED_PAY', 'name' => 'Other Deductions Payable'],
        'loan_recovery_payable' => ['code' => 'LOAN_REC', 'name' => 'Loan Recovery'],
        'advance_recovery_payable' => ['code' => 'ADV_REC', 'name' => 'Advance Recovery'],
        'reimbursement_expense' => ['code' => 'REIMB_EXP', 'name' => 'Reimbursement Expense'],
        'reimbursement_payable' => ['code' => 'REIMB_PAY', 'name' => 'Reimbursement Payable'],
    ];

    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
        protected PayrollPublicationService $publicationService,
    ) {}

    public function assertPublished(PayrollRun $run): void
    {
        if (! $run->isPublished() && $run->status !== 'reversed') {
            throw ValidationException::withMessages([
                'run' => 'Financial operations require a published payroll run.',
            ]);
        }
    }

    /**
     * Generate immutable ledger entries + balanced journal for a published run.
     *
     * @return array{entries: Collection<int, PayrollLedgerEntry>, journal: PayrollJournal}
     */
    public function generateLedgerForRun(PayrollRun $run, User $actor): array
    {
        return DB::transaction(function () use ($run, $actor): array {
            $run->refresh()->load(['results.employee', 'period', 'publication']);
            $this->assertPublished($run);

            if ($run->status === 'reversed') {
                throw ValidationException::withMessages([
                    'run' => 'Cannot generate ledger for a reversed payroll run.',
                ]);
            }

            $existing = PayrollLedgerEntry::query()
                ->where('payroll_run_id', $run->id)
                ->where('is_reversal', false)
                ->exists();

            if ($existing) {
                throw ValidationException::withMessages([
                    'run' => 'Ledger entries already exist for this payroll run.',
                ]);
            }

            $currency = (string) (config('hrms.payroll.default_currency', 'INR'));
            $entries = collect();
            $lineOrder = 0;

            foreach ($run->results as $result) {
                foreach ($this->buildLedgerLinesForResult($result) as $line) {
                    $entry = PayrollLedgerEntry::query()->create([
                        'organization_id' => $run->organization_id,
                        'payroll_run_id' => $run->id,
                        'payroll_result_id' => $result->id,
                        'employee_id' => $result->employee_id,
                        'account_code' => $line['account_code'],
                        'account_name' => $line['account_name'],
                        'entry_type' => $line['entry_type'],
                        'amount' => $line['amount'],
                        'currency' => $currency,
                        'description' => $line['description'],
                        'is_reversal' => false,
                        'meta' => $line['meta'] ?? null,
                        'generated_by' => $actor->id,
                        'generated_at' => now(),
                    ]);
                    $entries->push($entry);
                    $lineOrder++;
                }
            }

            // Include approved reimbursements into this publication's finance cycle.
            $includedReimbursements = $this->includeApprovedReimbursements($run, $actor);
            foreach ($includedReimbursements as $reimbursement) {
                $expense = self::ACCOUNT_MAP['reimbursement_expense'];
                $payable = self::ACCOUNT_MAP['reimbursement_payable'];

                foreach ([
                    ['account' => $expense, 'type' => 'debit'],
                    ['account' => $payable, 'type' => 'credit'],
                ] as $side) {
                    $entry = PayrollLedgerEntry::query()->create([
                        'organization_id' => $run->organization_id,
                        'payroll_run_id' => $run->id,
                        'payroll_result_id' => null,
                        'employee_id' => $reimbursement->employee_id,
                        'account_code' => $side['account']['code'],
                        'account_name' => $side['account']['name'],
                        'entry_type' => $side['type'],
                        'amount' => $reimbursement->amount,
                        'currency' => $currency,
                        'description' => 'Reimbursement '.$reimbursement->claim_number,
                        'is_reversal' => false,
                        'meta' => [
                            'reimbursement_id' => $reimbursement->id,
                            'is_taxable' => $reimbursement->is_taxable,
                        ],
                        'generated_by' => $actor->id,
                        'generated_at' => now(),
                    ]);
                    $entries->push($entry);
                }
            }

            $this->applyLoanRecoveriesForRun($run, $actor);
            $this->applyAdvanceRecoveriesForRun($run, $actor);

            $journal = $this->createJournalFromEntries($run, $entries, $actor, [
                'description' => 'Payroll journal for '.$run->period?->name,
            ]);

            $totalDebit = (float) $entries->where('entry_type', 'debit')->sum('amount');
            $totalCredit = (float) $entries->where('entry_type', 'credit')->sum('amount');

            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                throw ValidationException::withMessages([
                    'run' => 'Generated ledger is not balanced (debit '.$totalDebit.' vs credit '.$totalCredit.').',
                ]);
            }

            $this->auditLogger->log($run, 'payroll_ledger_generated', [
                'entry_count' => $entries->count(),
                'journal_id' => $journal->id,
                'journal_number' => $journal->journal_number,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
            ], $actor);

            $this->auditLogger->log($journal, 'payroll_journal_generated', [
                'payroll_run_id' => $run->id,
                'journal_number' => $journal->journal_number,
                'total_debit' => $journal->total_debit,
                'total_credit' => $journal->total_credit,
            ], $actor);

            event(PayrollLedgerGenerated::forModel($run->fresh(), [
                'actor_id' => $actor->id,
                'journal_id' => $journal->id,
                'entry_count' => $entries->count(),
            ]));

            return [
                'entries' => $entries,
                'journal' => $journal->fresh(['lines']),
            ];
        });
    }

    /**
     * @return list<array{account_code: string, account_name: string, entry_type: string, amount: float, description: string, meta?: array<string, mixed>}>
     */
    protected function buildLedgerLinesForResult(PayrollResult $result): array
    {
        $lines = [];
        $gross = round((float) $result->gross_salary, 2);
        $net = round((float) $result->net_salary, 2);
        $snapshot = $result->snapshot ?? [];
        $deductions = collect($snapshot['deductions'] ?? []);

        $employeeDeductions = $deductions
            ->filter(fn (array $line) => ($line['component_type'] ?? '') !== 'employer_contribution');
        $employerContributions = $deductions
            ->filter(fn (array $line) => ($line['component_type'] ?? '') === 'employer_contribution');

        if ($gross > 0) {
            $account = self::ACCOUNT_MAP['salary_expense'];
            $lines[] = [
                'account_code' => $account['code'],
                'account_name' => $account['name'],
                'entry_type' => 'debit',
                'amount' => $gross,
                'description' => 'Salary expense for employee #'.$result->employee_id,
            ];
        }

        $statutoryCredits = [
            'PF_EE' => 'pf_payable',
            'PF' => 'pf_payable',
            'ESI_EE' => 'esi_payable',
            'ESI' => 'esi_payable',
            'PT' => 'pt_payable',
            'TDS' => 'tds_payable',
            'IT' => 'tds_payable',
        ];

        foreach ($employeeDeductions as $deduction) {
            $code = strtoupper((string) ($deduction['code'] ?? ''));
            $amount = round((float) ($deduction['amount'] ?? 0), 2);
            if ($amount <= 0) {
                continue;
            }

            $mapKey = $statutoryCredits[$code] ?? null;
            if ($code === 'LOAN') {
                $mapKey = 'loan_recovery_payable';
            } elseif (in_array($code, ['ADVANCE', 'SALARY_ADVANCE'], true)) {
                $mapKey = 'advance_recovery_payable';
            }

            $account = self::ACCOUNT_MAP[$mapKey ?? 'other_deduction_payable'];
            $lines[] = [
                'account_code' => $account['code'],
                'account_name' => $account['name'],
                'entry_type' => 'credit',
                'amount' => $amount,
                'description' => ($deduction['name'] ?? $code).' deduction',
                'meta' => ['component_code' => $code],
            ];
        }

        foreach ($employerContributions as $contribution) {
            $code = strtoupper((string) ($contribution['code'] ?? ''));
            $amount = round((float) ($contribution['amount'] ?? 0), 2);
            if ($amount <= 0) {
                continue;
            }

            if (in_array($code, ['PF_ER', 'EPF_ER'], true)) {
                $expense = self::ACCOUNT_MAP['employer_pf_expense'];
                $payable = self::ACCOUNT_MAP['pf_payable'];
            } elseif (in_array($code, ['ESI_ER'], true)) {
                $expense = self::ACCOUNT_MAP['employer_esi_expense'];
                $payable = self::ACCOUNT_MAP['esi_payable'];
            } else {
                $expense = ['code' => 'ER_EXP', 'name' => 'Employer Contribution Expense'];
                $payable = self::ACCOUNT_MAP['other_deduction_payable'];
            }

            $lines[] = [
                'account_code' => $expense['code'],
                'account_name' => $expense['name'],
                'entry_type' => 'debit',
                'amount' => $amount,
                'description' => 'Employer '.$code,
                'meta' => ['component_code' => $code],
            ];
            $lines[] = [
                'account_code' => $payable['code'],
                'account_name' => $payable['name'],
                'entry_type' => 'credit',
                'amount' => $amount,
                'description' => 'Employer '.$code.' payable',
                'meta' => ['component_code' => $code],
            ];
        }

        if ($net > 0) {
            $account = self::ACCOUNT_MAP['salary_payable'];
            $lines[] = [
                'account_code' => $account['code'],
                'account_name' => $account['name'],
                'entry_type' => 'credit',
                'amount' => $net,
                'description' => 'Net salary payable',
            ];
        }

        // Balancing residual if gross - deductions != net due to rounding.
        $debit = collect($lines)->where('entry_type', 'debit')->sum('amount');
        $credit = collect($lines)->where('entry_type', 'credit')->sum('amount');
        $diff = round($debit - $credit, 2);
        if (abs($diff) >= 0.01) {
            $account = self::ACCOUNT_MAP['other_deduction_payable'];
            $lines[] = [
                'account_code' => $account['code'],
                'account_name' => $account['name'],
                'entry_type' => $diff > 0 ? 'credit' : 'debit',
                'amount' => abs($diff),
                'description' => 'Rounding adjustment',
                'meta' => ['rounding' => true],
            ];
        }

        return $lines;
    }

    /**
     * @param  Collection<int, PayrollLedgerEntry>  $entries
     * @param  array{description?: string, is_reversal?: bool, reverses_journal_id?: int|null}  $options
     */
    protected function createJournalFromEntries(
        PayrollRun $run,
        Collection $entries,
        User $actor,
        array $options = [],
    ): PayrollJournal {
        $totalDebit = round((float) $entries->where('entry_type', 'debit')->sum('amount'), 2);
        $totalCredit = round((float) $entries->where('entry_type', 'credit')->sum('amount'), 2);

        $journal = PayrollJournal::query()->create([
            'organization_id' => $run->organization_id,
            'payroll_run_id' => $run->id,
            'journal_number' => $this->nextDocumentNumber('PJ', 'payroll_journals', 'journal_number'),
            'journal_date' => now()->toDateString(),
            'description' => $options['description'] ?? 'Payroll journal',
            'status' => ($options['is_reversal'] ?? false) ? 'reversed' : 'posted',
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_reversal' => (bool) ($options['is_reversal'] ?? false),
            'reverses_journal_id' => $options['reverses_journal_id'] ?? null,
            'meta' => ['engine_version' => self::ENGINE_VERSION],
            'created_by' => $actor->id,
        ]);

        $order = 0;
        foreach ($entries as $entry) {
            PayrollJournalLine::query()->create([
                'organization_id' => $run->organization_id,
                'payroll_journal_id' => $journal->id,
                'payroll_ledger_entry_id' => $entry->id,
                'employee_id' => $entry->employee_id,
                'account_code' => $entry->account_code,
                'account_name' => $entry->account_name,
                'entry_type' => $entry->entry_type,
                'amount' => $entry->amount,
                'description' => $entry->description,
                'line_order' => $order++,
            ]);
        }

        return $journal;
    }

    /**
     * @return Collection<int, ExpenseReimbursement>
     */
    protected function includeApprovedReimbursements(PayrollRun $run, User $actor): Collection
    {
        $claims = ExpenseReimbursement::query()
            ->where('status', 'approved')
            ->whereNull('payroll_run_id')
            ->get();

        foreach ($claims as $claim) {
            $claim->update([
                'status' => 'included',
                'payroll_run_id' => $run->id,
                'included_at' => now(),
            ]);

            $this->auditLogger->log($claim, 'reimbursement_included', [
                'payroll_run_id' => $run->id,
                'amount' => $claim->amount,
            ], $actor);
        }

        return $claims;
    }

    public function applyLoanRecoveriesForRun(PayrollRun $run, User $actor): Collection
    {
        $recoveries = collect();
        $loans = EmployeeLoan::query()->where('status', 'active')->get();

        foreach ($loans as $loan) {
            $amount = min((float) $loan->monthly_recovery, (float) $loan->outstanding_balance);
            if ($amount <= 0) {
                continue;
            }

            $already = EmployeeLoanRecovery::query()
                ->where('employee_loan_id', $loan->id)
                ->where('payroll_run_id', $run->id)
                ->exists();

            if ($already) {
                continue;
            }

            $recovery = EmployeeLoanRecovery::query()->create([
                'organization_id' => $run->organization_id,
                'employee_loan_id' => $loan->id,
                'payroll_run_id' => $run->id,
                'payroll_period_id' => $run->payroll_period_id,
                'amount' => $amount,
                'recovery_type' => 'payroll',
                'recovered_at' => now(),
                'notes' => 'Payroll recovery',
                'recovered_by' => $actor->id,
            ]);

            $newBalance = round((float) $loan->outstanding_balance - $amount, 2);
            $loan->update([
                'outstanding_balance' => max(0, $newBalance),
                'status' => $newBalance <= 0 ? 'closed' : 'active',
                'closed_at' => $newBalance <= 0 ? now() : null,
                'closed_by' => $newBalance <= 0 ? $actor->id : null,
                'closure_reason' => $newBalance <= 0 ? 'Fully recovered via payroll' : null,
            ]);

            $recoveries->push($recovery);
        }

        return $recoveries;
    }

    public function applyAdvanceRecoveriesForRun(PayrollRun $run, User $actor): Collection
    {
        $recoveries = collect();
        $advances = SalaryAdvance::query()->where('status', 'active')->get();

        foreach ($advances as $advance) {
            $amount = min((float) $advance->monthly_recovery, (float) $advance->outstanding_balance);
            if ($amount <= 0) {
                continue;
            }

            $already = SalaryAdvanceRecovery::query()
                ->where('salary_advance_id', $advance->id)
                ->where('payroll_run_id', $run->id)
                ->exists();

            if ($already) {
                continue;
            }

            $recovery = SalaryAdvanceRecovery::query()->create([
                'organization_id' => $run->organization_id,
                'salary_advance_id' => $advance->id,
                'payroll_run_id' => $run->id,
                'payroll_period_id' => $run->payroll_period_id,
                'amount' => $amount,
                'recovery_type' => 'payroll',
                'recovered_at' => now(),
                'notes' => 'Payroll recovery',
                'recovered_by' => $actor->id,
            ]);

            $newBalance = round((float) $advance->outstanding_balance - $amount, 2);
            $advance->update([
                'outstanding_balance' => max(0, $newBalance),
                'status' => $newBalance <= 0 ? 'closed' : 'active',
            ]);

            $recoveries->push($recovery);
        }

        return $recoveries;
    }

    public function generateBankExport(PayrollRun $run, User $actor, string $format = 'csv'): PayrollBankExport
    {
        return DB::transaction(function () use ($run, $actor, $format): PayrollBankExport {
            $run->refresh()->load(['results.employee.bankAccounts', 'period']);
            $this->assertPublished($run);

            if (! in_array($format, ['csv', 'xlsx'], true)) {
                throw ValidationException::withMessages([
                    'format' => 'Export format must be csv or xlsx.',
                ]);
            }

            $rows = [];
            $total = 0.0;

            foreach ($run->results as $result) {
                $employee = $result->employee;
                $bank = $employee?->bankAccounts
                    ?->firstWhere('is_primary', true)
                    ?? $employee?->bankAccounts?->first();

                $reimbursementExtra = (float) ExpenseReimbursement::query()
                    ->where('payroll_run_id', $run->id)
                    ->where('employee_id', $result->employee_id)
                    ->where('status', 'included')
                    ->sum('amount');

                $amount = round((float) $result->net_salary + $reimbursementExtra, 2);
                $total += $amount;

                $rows[] = [
                    'employee_code' => $employee?->employee_code ?? (string) $result->employee_id,
                    'employee_name' => $employee?->full_name ?? '',
                    'bank_name' => $bank?->bank_name ?? '',
                    'account_number' => $bank?->account_number ?? '',
                    'ifsc' => $bank?->ifsc_or_swift ?? '',
                    'amount' => number_format($amount, 2, '.', ''),
                    'reference' => 'PAY-'.$run->id.'-'.$result->employee_id,
                ];
            }

            $exportNumber = $this->nextDocumentNumber('BE', 'payroll_bank_exports', 'export_number');
            $disk = config('hrms.payslips.disk', 'local');
            $path = 'hrms-bank-exports/'.$run->organization_id.'/'.$exportNumber.'.'.$format;

            if ($format === 'csv') {
                $this->writeCsvExport($disk, $path, $rows);
            } else {
                $this->writeXlsxExport($disk, $path, $rows);
            }

            $export = PayrollBankExport::query()->create([
                'organization_id' => $run->organization_id,
                'payroll_run_id' => $run->id,
                'export_number' => $exportNumber,
                'format' => $format,
                'file_disk' => $disk,
                'file_path' => $path,
                'employee_count' => count($rows),
                'total_amount' => round($total, 2),
                'status' => 'generated',
                'meta' => ['engine_version' => self::ENGINE_VERSION],
                'exported_by' => $actor->id,
                'exported_at' => now(),
            ]);

            $this->auditLogger->log($export, 'payroll_bank_exported', [
                'payroll_run_id' => $run->id,
                'format' => $format,
                'employee_count' => count($rows),
                'total_amount' => $export->total_amount,
            ], $actor);

            event(PayrollBankExported::forModel($export, [
                'actor_id' => $actor->id,
                'payroll_run_id' => $run->id,
            ]));

            return $export;
        });
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    protected function writeCsvExport(string $disk, string $path, array $rows): void
    {
        $headers = ['employee_code', 'employee_name', 'bank_name', 'account_number', 'ifsc', 'amount', 'reference'];
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn (string $key) => $row[$key] ?? '', $headers));
        }
        rewind($handle);
        Storage::disk($disk)->put($path, stream_get_contents($handle) ?: '');
        fclose($handle);
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    protected function writeXlsxExport(string $disk, string $path, array $rows): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['employee_code', 'employee_name', 'bank_name', 'account_number', 'ifsc', 'amount', 'reference'];
        foreach ($headers as $i => $header) {
            $sheet->setCellValue([$i + 1, 1], $header);
        }
        foreach ($rows as $rowIndex => $row) {
            foreach ($headers as $colIndex => $header) {
                $sheet->setCellValue([$colIndex + 1, $rowIndex + 2], $row[$header] ?? '');
            }
        }

        $tmp = tempnam(sys_get_temp_dir(), 'be');
        (new Xlsx($spreadsheet))->save($tmp);
        Storage::disk($disk)->put($path, file_get_contents($tmp) ?: '');
        @unlink($tmp);
    }

    /**
     * @param  array{employee_id: int, loan_type?: string, principal_amount: float|int|string, monthly_recovery: float|int|string, interest_rate?: float|null, disbursed_on?: string|null, notes?: string|null}  $data
     */
    public function createLoan(array $data, User $actor): EmployeeLoan
    {
        return DB::transaction(function () use ($data, $actor): EmployeeLoan {
            $employee = Employee::query()->findOrFail($data['employee_id']);
            $principal = round((float) $data['principal_amount'], 2);

            $loan = EmployeeLoan::query()->create([
                'organization_id' => $employee->organization_id,
                'employee_id' => $employee->id,
                'loan_number' => $this->nextDocumentNumber('LN', 'employee_loans', 'loan_number'),
                'loan_type' => $data['loan_type'] ?? 'general',
                'principal_amount' => $principal,
                'outstanding_balance' => $principal,
                'monthly_recovery' => round((float) $data['monthly_recovery'], 2),
                'interest_rate' => isset($data['interest_rate']) ? (float) $data['interest_rate'] : null,
                'disbursed_on' => $data['disbursed_on'] ?? now()->toDateString(),
                'status' => 'active',
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $this->auditLogger->log($loan, 'employee_loan_created', [
                'principal_amount' => $loan->principal_amount,
                'monthly_recovery' => $loan->monthly_recovery,
            ], $actor);

            event(EmployeeLoanCreated::forModel($loan, [
                'actor_id' => $actor->id,
            ]));

            return $loan;
        });
    }

    public function closeLoan(EmployeeLoan $loan, User $actor, ?string $reason = null): EmployeeLoan
    {
        return DB::transaction(function () use ($loan, $actor, $reason): EmployeeLoan {
            $loan->refresh();

            if ($loan->isClosed()) {
                throw ValidationException::withMessages([
                    'loan' => 'Loan is already closed.',
                ]);
            }

            $balance = round((float) $loan->outstanding_balance, 2);
            if ($balance > 0) {
                EmployeeLoanRecovery::query()->create([
                    'organization_id' => $loan->organization_id,
                    'employee_loan_id' => $loan->id,
                    'amount' => $balance,
                    'recovery_type' => 'closure',
                    'recovered_at' => now(),
                    'notes' => $reason ?? 'Early closure',
                    'recovered_by' => $actor->id,
                ]);
            }

            $loan->update([
                'outstanding_balance' => 0,
                'status' => 'closed',
                'closed_at' => now(),
                'closed_by' => $actor->id,
                'closure_reason' => $reason ?? 'Early closure',
            ]);

            $this->auditLogger->log($loan, 'employee_loan_closed', [
                'closure_reason' => $loan->closure_reason,
                'recovered_on_closure' => $balance,
            ], $actor);

            event(EmployeeLoanClosed::forModel($loan->fresh(), [
                'actor_id' => $actor->id,
            ]));

            return $loan->fresh();
        });
    }

    /**
     * @param  array{employee_id: int, amount: float|int|string, monthly_recovery: float|int|string, reason?: string|null, notes?: string|null}  $data
     */
    public function createAdvance(array $data, User $actor): SalaryAdvance
    {
        return DB::transaction(function () use ($data, $actor): SalaryAdvance {
            $employee = Employee::query()->findOrFail($data['employee_id']);
            $amount = round((float) $data['amount'], 2);

            $advance = SalaryAdvance::query()->create([
                'organization_id' => $employee->organization_id,
                'employee_id' => $employee->id,
                'advance_number' => $this->nextDocumentNumber('SA', 'salary_advances', 'advance_number'),
                'amount' => $amount,
                'outstanding_balance' => $amount,
                'monthly_recovery' => round((float) $data['monthly_recovery'], 2),
                'status' => 'pending',
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'requested_by' => $actor->id,
                'requested_at' => now(),
            ]);

            $this->auditLogger->log($advance, 'salary_advance_requested', [
                'amount' => $advance->amount,
            ], $actor);

            return $advance;
        });
    }

    public function approveAdvance(SalaryAdvance $advance, User $actor): SalaryAdvance
    {
        return DB::transaction(function () use ($advance, $actor): SalaryAdvance {
            if (! $advance->isPending()) {
                throw ValidationException::withMessages([
                    'advance' => 'Only pending advances can be approved.',
                ]);
            }

            $advance->update([
                'status' => 'active',
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $this->auditLogger->log($advance, 'salary_advance_approved', [
                'amount' => $advance->amount,
            ], $actor);

            return $advance->fresh();
        });
    }

    public function rejectAdvance(SalaryAdvance $advance, User $actor, ?string $reason = null): SalaryAdvance
    {
        return DB::transaction(function () use ($advance, $actor, $reason): SalaryAdvance {
            if (! $advance->isPending()) {
                throw ValidationException::withMessages([
                    'advance' => 'Only pending advances can be rejected.',
                ]);
            }

            $advance->update([
                'status' => 'rejected',
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'rejection_reason' => $reason,
                'outstanding_balance' => 0,
            ]);

            $this->auditLogger->log($advance, 'salary_advance_rejected', [
                'rejection_reason' => $reason,
            ], $actor);

            return $advance->fresh();
        });
    }

    /**
     * @param  array{employee_id: int, amount: float|int|string, category?: string, is_taxable?: bool, description?: string|null}  $data
     */
    public function createReimbursement(array $data, User $actor): ExpenseReimbursement
    {
        return DB::transaction(function () use ($data, $actor): ExpenseReimbursement {
            $employee = Employee::query()->findOrFail($data['employee_id']);

            $claim = ExpenseReimbursement::query()->create([
                'organization_id' => $employee->organization_id,
                'employee_id' => $employee->id,
                'claim_number' => $this->nextDocumentNumber('RB', 'expense_reimbursements', 'claim_number'),
                'category' => $data['category'] ?? 'general',
                'amount' => round((float) $data['amount'], 2),
                'is_taxable' => (bool) ($data['is_taxable'] ?? false),
                'status' => 'pending',
                'description' => $data['description'] ?? null,
                'requested_by' => $actor->id,
                'requested_at' => now(),
            ]);

            $this->auditLogger->log($claim, 'reimbursement_requested', [
                'amount' => $claim->amount,
            ], $actor);

            return $claim;
        });
    }

    public function approveReimbursement(ExpenseReimbursement $claim, User $actor): ExpenseReimbursement
    {
        return DB::transaction(function () use ($claim, $actor): ExpenseReimbursement {
            if ($claim->status !== 'pending') {
                throw ValidationException::withMessages([
                    'reimbursement' => 'Only pending reimbursements can be approved.',
                ]);
            }

            $claim->update([
                'status' => 'approved',
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $this->auditLogger->log($claim, 'reimbursement_approved', [
                'amount' => $claim->amount,
                'is_taxable' => $claim->is_taxable,
            ], $actor);

            return $claim->fresh();
        });
    }

    public function rejectReimbursement(ExpenseReimbursement $claim, User $actor, ?string $reason = null): ExpenseReimbursement
    {
        return DB::transaction(function () use ($claim, $actor, $reason): ExpenseReimbursement {
            if ($claim->status !== 'pending') {
                throw ValidationException::withMessages([
                    'reimbursement' => 'Only pending reimbursements can be rejected.',
                ]);
            }

            $claim->update([
                'status' => 'rejected',
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $this->auditLogger->log($claim, 'reimbursement_rejected', [
                'rejection_reason' => $reason,
            ], $actor);

            return $claim->fresh();
        });
    }

    /**
     * @param  array{pending_salary?: float|int|string, leave_encashment?: float|int|string, asset_deductions?: float|int|string, statutory_deductions?: float|int|string, notes?: string|null, employee_exit_process_id?: int|null}  $overrides
     */
    public function generateSettlement(Employee $employee, User $actor, array $overrides = []): EmployeeSettlement
    {
        return DB::transaction(function () use ($employee, $actor, $overrides): EmployeeSettlement {
            $loanRecovery = (float) EmployeeLoan::query()
                ->where('employee_id', $employee->id)
                ->where('status', 'active')
                ->sum('outstanding_balance');

            $advanceRecovery = (float) SalaryAdvance::query()
                ->where('employee_id', $employee->id)
                ->where('status', 'active')
                ->sum('outstanding_balance');

            $reimbursements = (float) ExpenseReimbursement::query()
                ->where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->sum('amount');

            $leaveDays = (float) LeaveBalance::query()
                ->where('employee_id', $employee->id)
                ->where('year', (int) now()->year)
                ->sum('balance');

            $dailyRate = $this->estimateDailyRate($employee);
            $computedLeaveEncashment = round($leaveDays * $dailyRate, 2);

            $pendingSalary = round((float) ($overrides['pending_salary'] ?? 0), 2);
            $leaveEncashment = array_key_exists('leave_encashment', $overrides)
                ? round((float) $overrides['leave_encashment'], 2)
                : $computedLeaveEncashment;
            $assetDeductions = round((float) ($overrides['asset_deductions'] ?? 0), 2);
            $statutoryDeductions = round((float) ($overrides['statutory_deductions'] ?? 0), 2);

            $net = round(
                $pendingSalary
                + $leaveEncashment
                + $reimbursements
                - $loanRecovery
                - $advanceRecovery
                - $assetDeductions
                - $statutoryDeductions,
                2
            );

            $statement = [
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name ?? $employee->name,
                'as_of' => now()->toDateTimeString(),
                'components' => [
                    'pending_salary' => $pendingSalary,
                    'leave_encashment' => $leaveEncashment,
                    'leave_days' => $leaveDays,
                    'reimbursements' => $reimbursements,
                    'loan_recovery' => $loanRecovery,
                    'advance_recovery' => $advanceRecovery,
                    'asset_deductions' => $assetDeductions,
                    'statutory_deductions' => $statutoryDeductions,
                ],
                'net_settlement' => $net,
                'engine_version' => self::ENGINE_VERSION,
            ];

            $settlement = EmployeeSettlement::query()->create([
                'organization_id' => $employee->organization_id,
                'employee_id' => $employee->id,
                'employee_exit_process_id' => $overrides['employee_exit_process_id'] ?? null,
                'settlement_number' => $this->nextDocumentNumber('FS', 'employee_settlements', 'settlement_number'),
                'status' => 'completed',
                'pending_salary' => $pendingSalary,
                'leave_encashment' => $leaveEncashment,
                'loan_recovery' => $loanRecovery,
                'advance_recovery' => $advanceRecovery,
                'reimbursements' => $reimbursements,
                'asset_deductions' => $assetDeductions,
                'statutory_deductions' => $statutoryDeductions,
                'net_settlement' => $net,
                'statement' => $statement,
                'notes' => $overrides['notes'] ?? null,
                'completed_by' => $actor->id,
                'completed_at' => now(),
            ]);

            // Close outstanding loans/advances against settlement recovery.
            foreach (EmployeeLoan::query()->where('employee_id', $employee->id)->where('status', 'active')->get() as $loan) {
                $this->closeLoan($loan, $actor, 'Recovered via final settlement '.$settlement->settlement_number);
            }
            foreach (SalaryAdvance::query()->where('employee_id', $employee->id)->where('status', 'active')->get() as $advance) {
                $advance->update([
                    'outstanding_balance' => 0,
                    'status' => 'closed',
                ]);
            }
            ExpenseReimbursement::query()
                ->where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->update(['status' => 'included', 'included_at' => now()]);

            $this->auditLogger->log($settlement, 'employee_settlement_completed', [
                'net_settlement' => $settlement->net_settlement,
                'settlement_number' => $settlement->settlement_number,
            ], $actor);

            event(EmployeeSettlementCompleted::forModel($settlement, [
                'actor_id' => $actor->id,
            ]));

            return $settlement->fresh(['employee']);
        });
    }

    protected function estimateDailyRate(Employee $employee): float
    {
        $assignment = $employee->salaryAssignments()
            ->whereNull('effective_until')
            ->latest('effective_from')
            ->first();

        if (! $assignment || ! $assignment->annual_ctc) {
            return 0.0;
        }

        return round(((float) $assignment->annual_ctc / 12) / 30, 2);
    }

    public function reversePayroll(PayrollRun $run, User $actor, string $reason): PayrollReversal
    {
        return DB::transaction(function () use ($run, $actor, $reason): PayrollReversal {
            $run->refresh();

            if (! $run->isPublished()) {
                throw ValidationException::withMessages([
                    'run' => 'Only published payroll runs can be reversed.',
                ]);
            }

            if (PayrollReversal::query()->where('payroll_run_id', $run->id)->exists()) {
                throw ValidationException::withMessages([
                    'run' => 'Payroll run has already been reversed.',
                ]);
            }

            if (trim($reason) === '') {
                throw ValidationException::withMessages([
                    'reason' => 'A reversal reason is mandatory.',
                ]);
            }

            $originalEntries = PayrollLedgerEntry::query()
                ->where('payroll_run_id', $run->id)
                ->where('is_reversal', false)
                ->get();

            if ($originalEntries->isEmpty()) {
                // Generate ledger first so reversal has something to reverse.
                $this->generateLedgerForRun($run, $actor);
                $originalEntries = PayrollLedgerEntry::query()
                    ->where('payroll_run_id', $run->id)
                    ->where('is_reversal', false)
                    ->get();
            }

            $originalJournal = PayrollJournal::query()
                ->where('payroll_run_id', $run->id)
                ->where('is_reversal', false)
                ->latest('id')
                ->first();

            $reversingEntries = collect();
            foreach ($originalEntries as $entry) {
                $reversing = PayrollLedgerEntry::query()->create([
                    'organization_id' => $entry->organization_id,
                    'payroll_run_id' => $run->id,
                    'payroll_result_id' => $entry->payroll_result_id,
                    'employee_id' => $entry->employee_id,
                    'account_code' => $entry->account_code,
                    'account_name' => $entry->account_name,
                    'entry_type' => $entry->entry_type === 'debit' ? 'credit' : 'debit',
                    'amount' => $entry->amount,
                    'currency' => $entry->currency,
                    'description' => 'Reversal: '.($entry->description ?? ''),
                    'is_reversal' => true,
                    'reverses_entry_id' => $entry->id,
                    'meta' => ['reversal_reason' => $reason],
                    'generated_by' => $actor->id,
                    'generated_at' => now(),
                ]);
                $reversingEntries->push($reversing);
            }

            $reversingJournal = $this->createJournalFromEntries($run, $reversingEntries, $actor, [
                'description' => 'Reversing journal for '.$run->period?->name,
                'is_reversal' => true,
                'reverses_journal_id' => $originalJournal?->id,
            ]);

            if ($originalJournal) {
                $originalJournal->update(['status' => 'reversed']);
            }

            $reversal = PayrollReversal::query()->create([
                'organization_id' => $run->organization_id,
                'payroll_run_id' => $run->id,
                'reversal_number' => $this->nextDocumentNumber('PR', 'payroll_reversals', 'reversal_number'),
                'reason' => $reason,
                'reversing_journal_id' => $reversingJournal->id,
                'meta' => ['engine_version' => self::ENGINE_VERSION],
                'reversed_by' => $actor->id,
                'reversed_at' => now(),
            ]);

            // Never delete payroll — mark reversed while keeping payslips/results.
            $run->update(['status' => 'reversed']);

            $this->auditLogger->log($run, 'payroll_reversed', [
                'reversal_id' => $reversal->id,
                'reversal_number' => $reversal->reversal_number,
                'reason' => $reason,
                'reversing_journal_id' => $reversingJournal->id,
            ], $actor);

            event(PayrollReversed::forModel($run->fresh(), [
                'actor_id' => $actor->id,
                'reversal_id' => $reversal->id,
                'reason' => $reason,
            ]));

            return $reversal->fresh(['reversingJournal', 'payrollRun']);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function reportPayrollSummary(?int $payrollRunId = null): array
    {
        $query = PayrollRun::query()->whereIn('status', ['published', 'reversed']);
        if ($payrollRunId) {
            $query->where('id', $payrollRunId);
        }

        $runs = $query->with('results')->get();

        return [
            'run_count' => $runs->count(),
            'employee_count' => $runs->sum(fn (PayrollRun $run) => $run->results->count()),
            'gross_salary' => round((float) $runs->sum(fn (PayrollRun $run) => $run->results->sum('gross_salary')), 2),
            'total_deductions' => round((float) $runs->sum(fn (PayrollRun $run) => $run->results->sum('total_deductions')), 2),
            'net_salary' => round((float) $runs->sum(fn (PayrollRun $run) => $run->results->sum('net_salary')), 2),
        ];
    }

    /**
     * @return array<string, float>
     */
    public function reportStatutoryLiability(?int $payrollRunId = null): array
    {
        $query = PayrollLedgerEntry::query()
            ->where('is_reversal', false)
            ->whereIn('account_code', ['PF_PAY', 'ESI_PAY', 'PT_PAY', 'TDS_PAY']);

        if ($payrollRunId) {
            $query->where('payroll_run_id', $payrollRunId);
        }

        return $query->get()
            ->groupBy('account_code')
            ->map(fn (Collection $rows) => round((float) $rows->where('entry_type', 'credit')->sum('amount')
                - (float) $rows->where('entry_type', 'debit')->sum('amount'), 2))
            ->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function reportSalaryRegister(?int $payrollRunId = null): Collection
    {
        $query = PayrollResult::query()->with(['employee', 'payrollRun.period']);
        if ($payrollRunId) {
            $query->where('payroll_run_id', $payrollRunId);
        } else {
            $query->whereHas('payrollRun', fn ($q) => $q->whereIn('status', ['published', 'reversed']));
        }

        return $query->get()->map(fn (PayrollResult $result) => [
            'employee_id' => $result->employee_id,
            'employee_name' => $result->employee?->full_name ?? $result->employee?->name,
            'period' => $result->payrollRun?->period?->name,
            'gross_salary' => (float) $result->gross_salary,
            'total_deductions' => (float) $result->total_deductions,
            'net_salary' => (float) $result->net_salary,
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function reportBranchSalary(?int $payrollRunId = null): Collection
    {
        return $this->reportSalaryRegister($payrollRunId)
            ->groupBy(function (array $row) {
                $employee = Employee::query()->with('branch')->find($row['employee_id']);

                return $employee?->branch?->name ?? 'Unassigned';
            })
            ->map(fn (Collection $rows, string $branch) => [
                'branch' => $branch,
                'employee_count' => $rows->count(),
                'gross_salary' => round((float) $rows->sum('gross_salary'), 2),
                'net_salary' => round((float) $rows->sum('net_salary'), 2),
            ])
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function reportDepartmentSalary(?int $payrollRunId = null): Collection
    {
        return $this->reportSalaryRegister($payrollRunId)
            ->groupBy(function (array $row) {
                $employee = Employee::query()->with('department')->find($row['employee_id']);

                return $employee?->department?->name ?? 'Unassigned';
            })
            ->map(fn (Collection $rows, string $department) => [
                'department' => $department,
                'employee_count' => $rows->count(),
                'gross_salary' => round((float) $rows->sum('gross_salary'), 2),
                'net_salary' => round((float) $rows->sum('net_salary'), 2),
            ])
            ->values();
    }

    /**
     * Export a report payload as CSV or XLSX content string / binary.
     *
     * @return array{disk: string, path: string, format: string, filename: string}
     */
    public function exportReport(string $report, string $format = 'csv', ?int $payrollRunId = null): array
    {
        if (! in_array($format, ['csv', 'xlsx'], true)) {
            throw ValidationException::withMessages([
                'format' => 'Export format must be csv or xlsx.',
            ]);
        }

        $rows = match ($report) {
            'statutory' => collect($this->reportStatutoryLiability($payrollRunId))
                ->map(fn ($amount, $code) => ['account' => $code, 'liability' => $amount])
                ->values()
                ->all(),
            'salary_register' => $this->reportSalaryRegister($payrollRunId)->all(),
            'department' => $this->reportDepartmentSalary($payrollRunId)->all(),
            'branch' => $this->reportBranchSalary($payrollRunId)->all(),
            'cost_center' => $this->reportCostCenterSummary($payrollRunId)->all(),
            default => [ $this->reportPayrollSummary($payrollRunId) ],
        };

        $disk = config('hrms.payslips.disk', 'local');
        $filename = 'payroll-report-'.$report.'-'.now()->format('YmdHis').'.'.$format;
        $path = 'hrms-payroll-reports/'.$this->tenantContext->id().'/'.$filename;

        $normalized = array_map(function ($row) {
            if (! is_array($row)) {
                return ['value' => (string) $row];
            }

            return array_map(fn ($value) => is_scalar($value) || $value === null ? (string) $value : json_encode($value), $row);
        }, $rows);

        if ($format === 'csv') {
            $this->writeGenericCsvExport($disk, $path, $normalized);
        } else {
            $this->writeGenericXlsxExport($disk, $path, $normalized);
        }

        return compact('disk', 'path', 'format', 'filename');
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    protected function writeGenericCsvExport(string $disk, string $path, array $rows): void
    {
        $headers = $rows === [] ? ['value'] : array_keys($rows[0]);
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn (string $key) => $row[$key] ?? '', $headers));
        }
        rewind($handle);
        Storage::disk($disk)->put($path, stream_get_contents($handle) ?: '');
        fclose($handle);
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    protected function writeGenericXlsxExport(string $disk, string $path, array $rows): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $headers = $rows === [] ? ['value'] : array_keys($rows[0]);
        foreach ($headers as $i => $header) {
            $sheet->setCellValue([$i + 1, 1], $header);
        }
        foreach ($rows as $rowIndex => $row) {
            foreach ($headers as $colIndex => $header) {
                $sheet->setCellValue([$colIndex + 1, $rowIndex + 2], $row[$header] ?? '');
            }
        }

        $tmp = tempnam(sys_get_temp_dir(), 'pr');
        (new Xlsx($spreadsheet))->save($tmp);
        Storage::disk($disk)->put($path, file_get_contents($tmp) ?: '');
        @unlink($tmp);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function reportCostCenterSummary(?int $payrollRunId = null): Collection
    {
        // Cost center mirrors department until dedicated cost centers exist.
        return $this->reportDepartmentSalary($payrollRunId);
    }

    /**
     * @return Collection<int, PayrollLedgerEntry>
     */
    public function reportLedger(?int $payrollRunId = null): Collection
    {
        $query = PayrollLedgerEntry::query()->with(['employee', 'payrollRun'])->orderBy('id');
        if ($payrollRunId) {
            $query->where('payroll_run_id', $payrollRunId);
        }

        return $query->get();
    }

    protected function nextDocumentNumber(string $prefix, string $table, string $column): string
    {
        $organizationId = $this->tenantContext->id();
        $seq = DB::table($table)
            ->where('organization_id', $organizationId)
            ->count() + 1;

        return sprintf('%s-%s-%06d', $prefix, now()->format('Ym'), $seq);
    }
}
