<?php

namespace App\Services\Hrms;

use App\Events\PayrollApproved;
use App\Events\PayrollPublished;
use App\Events\PayslipEmailed;
use App\Events\PayslipGenerated;
use App\Jobs\SendPayslipEmailJob;
use App\Mail\PayslipMail;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\PayrollApproval;
use App\Models\PayrollPublication;
use App\Models\PayrollResult;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Services\OrganizationMailer;
use App\Services\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PayrollPublicationService
{
    public const ENGINE_VERSION = '10.3.4';

    /** @var array<string, list<string>> */
    public const ALLOWED_TRANSITIONS = [
        'draft' => ['running'],
        'running' => ['calculated'],
        'calculated' => ['approved'],
        'approved' => ['published'],
        'published' => [],
    ];

    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
        protected OrganizationMailer $organizationMailer,
        protected NotificationService $notificationService,
    ) {}

    public function assertTransition(PayrollRun $run, string $to): void
    {
        $from = $run->status;
        $allowed = self::ALLOWED_TRANSITIONS[$from] ?? [];

        if (! in_array($to, $allowed, true)) {
            throw ValidationException::withMessages([
                'run' => "Payroll run cannot transition from {$from} to {$to}.",
            ]);
        }
    }

    /**
     * @param  array{approval_type?: string, notes?: string|null}  $data
     */
    public function approveRun(PayrollRun $run, User $actor, array $data = []): PayrollApproval
    {
        return DB::transaction(function () use ($run, $actor, $data): PayrollApproval {
            $run->refresh();
            $this->assertTransition($run, 'approved');

            if ($run->results()->count() === 0) {
                throw ValidationException::withMessages([
                    'run' => 'Cannot approve a payroll run with no results.',
                ]);
            }

            $approvalType = $data['approval_type'] ?? 'hr';

            $approval = PayrollApproval::query()->create([
                'organization_id' => $run->organization_id,
                'payroll_run_id' => $run->id,
                'approval_type' => $approvalType,
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $run->update(['status' => 'approved']);

            $this->auditLogger->log($run, 'payroll_approved', [
                'approval_id' => $approval->id,
                'approval_type' => $approvalType,
                'notes' => $approval->notes,
            ], $actor);

            event(PayrollApproved::forModel($run->fresh(), [
                'actor_id' => $actor->id,
                'approval_id' => $approval->id,
                'approval_type' => $approvalType,
            ]));

            return $approval->load(['approvedBy', 'payrollRun']);
        });
    }

    /**
     * Publish an approved run: lock, generate payslips + PDFs, notify employees.
     *
     * @param  array{send_emails?: bool}  $options
     */
    public function publishRun(PayrollRun $run, User $actor, array $options = []): PayrollPublication
    {
        return DB::transaction(function () use ($run, $actor, $options): PayrollPublication {
            $run->refresh()->load(['period', 'results.employee']);
            $this->assertTransition($run, 'published');

            $sendEmails = (bool) ($options['send_emails'] ?? true);

            $publication = PayrollPublication::query()->create([
                'organization_id' => $run->organization_id,
                'payroll_run_id' => $run->id,
                'published_by' => $actor->id,
                'published_at' => now(),
                'payslip_count' => 0,
                'email_queued_count' => 0,
                'status' => 'publishing',
                'meta' => ['engine_version' => self::ENGINE_VERSION],
            ]);

            $payslips = [];
            $emailsQueued = 0;

            foreach ($run->results as $result) {
                $payslip = $this->generatePayslip($run, $result, $publication, $actor);
                $payslips[] = $payslip;

                if ($sendEmails) {
                    $queued = $this->queuePayslipEmail($payslip, $actor);
                    if ($queued) {
                        $emailsQueued++;
                    }
                }

                $this->notifyEmployeePublished($payslip);
            }

            $publication->update([
                'payslip_count' => count($payslips),
                'email_queued_count' => $emailsQueued,
                'status' => 'published',
            ]);

            $run->update(['status' => 'published']);

            // Lock the related period when publishing.
            if ($run->period && ! $run->period->isLocked()) {
                $run->period->update(['status' => 'locked']);
            }

            $this->auditLogger->log($run, 'payroll_published', [
                'publication_id' => $publication->id,
                'payslip_count' => count($payslips),
                'email_queued_count' => $emailsQueued,
            ], $actor);

            event(PayrollPublished::forModel($run->fresh(), [
                'actor_id' => $actor->id,
                'publication_id' => $publication->id,
                'payslip_count' => count($payslips),
            ]));

            return $publication->fresh(['payslips', 'publishedBy', 'payrollRun']);
        });
    }

    public function generatePayslip(
        PayrollRun $run,
        PayrollResult $result,
        PayrollPublication $publication,
        ?User $actor = null,
    ): Payslip {
        if (Payslip::query()->where('payroll_result_id', $result->id)->exists()) {
            throw ValidationException::withMessages([
                'result' => 'A payslip already exists for this payroll result.',
            ]);
        }

        $snapshot = $result->snapshot ?? [];
        $employerContributions = (float) ($snapshot['totals']['employer_contributions'] ?? 0);
        if ($employerContributions <= 0) {
            $employerContributions = collect($snapshot['deductions'] ?? [])
                ->where('component_type', 'employer_contribution')
                ->sum('amount');
        }

        $payslip = Payslip::query()->create([
            'organization_id' => $run->organization_id,
            'payroll_run_id' => $run->id,
            'payroll_result_id' => $result->id,
            'payroll_publication_id' => $publication->id,
            'employee_id' => $result->employee_id,
            'payslip_number' => $this->nextPayslipNumber($run, $result),
            'gross_salary' => $result->gross_salary,
            'total_earnings' => $result->total_earnings,
            'total_deductions' => $result->total_deductions,
            'employer_contributions' => $employerContributions,
            'net_salary' => $result->net_salary,
            'snapshot' => $snapshot,
            'calculation_hash' => $result->calculation_hash,
            'generated_at' => now(),
        ]);

        $this->storePayslipPdf($payslip->fresh(['employee', 'payrollRun.period']));

        $this->auditLogger->log($payslip, 'payslip_generated', [
            'payslip_number' => $payslip->payslip_number,
            'payroll_result_id' => $result->id,
            'employee_id' => $result->employee_id,
        ], $actor);

        event(PayslipGenerated::forModel($payslip->fresh(), [
            'actor_id' => $actor?->id,
            'employee_id' => $result->employee_id,
            'payroll_run_id' => $run->id,
        ]));

        return $payslip->fresh();
    }

    public function storePayslipPdf(Payslip $payslip): Payslip
    {
        if ($payslip->hasPdf() && $payslip->pdfExists()) {
            return $payslip;
        }

        $payslip->loadMissing(['employee', 'payrollRun.period', 'organization']);
        $organization = $payslip->organization ?? Organization::query()->find($payslip->organization_id);

        $pdf = Pdf::loadView('pdf.payslip', [
            'payslip' => $payslip,
            'organization' => $organization,
            'employee' => $payslip->employee,
            'period' => $payslip->payrollRun?->period,
            'earnings' => collect($payslip->snapshot['earnings'] ?? []),
            'deductions' => collect($payslip->snapshot['deductions'] ?? [])
                ->filter(fn (array $line) => ($line['component_type'] ?? '') !== 'employer_contribution'),
            'employerContributions' => collect($payslip->snapshot['deductions'] ?? [])
                ->filter(fn (array $line) => ($line['component_type'] ?? '') === 'employer_contribution'),
            'statutory' => $payslip->snapshot['statutory'] ?? null,
        ])->setPaper('a4');

        $disk = config('hrms.payslips.disk', config('hrms.documents.disk', 'local'));
        $directory = sprintf(
            'hrms-payslips/%d/%d',
            $payslip->organization_id,
            $payslip->employee_id
        );
        $filename = $payslip->payslip_number.'.pdf';
        $path = $directory.'/'.$filename;

        Storage::disk($disk)->put($path, $pdf->output());

        $payslip->update([
            'pdf_disk' => $disk,
            'pdf_path' => $path,
        ]);

        return $payslip->fresh();
    }

    public function queuePayslipEmail(Payslip $payslip, ?User $actor = null): bool
    {
        $payslip->loadMissing(['employee.user', 'organization']);
        $employee = $payslip->employee;
        $email = $employee?->email ?: $employee?->user?->email;

        if (! filled($email)) {
            return false;
        }

        $organization = $payslip->organization ?? Organization::query()->find($payslip->organization_id);
        if (! $organization || ! $this->organizationMailer->isConfigured($organization)) {
            return false;
        }

        SendPayslipEmailJob::dispatch($payslip->id, $actor?->id)->afterCommit();

        return true;
    }

    public function sendPayslipEmail(Payslip $payslip, ?User $actor = null): void
    {
        $payslip->loadMissing(['employee.user', 'organization', 'payrollRun.period']);

        if (! $payslip->hasPdf() || ! $payslip->pdfExists()) {
            $this->storePayslipPdf($payslip);
            $payslip->refresh();
        }

        $employee = $payslip->employee;
        $email = $employee?->email ?: $employee?->user?->email;

        if (! filled($email)) {
            throw ValidationException::withMessages([
                'email' => 'Employee does not have an email address for payslip delivery.',
            ]);
        }

        $organization = $payslip->organization ?? Organization::query()->findOrFail($payslip->organization_id);

        $this->organizationMailer->send(
            $organization,
            $email,
            new PayslipMail($payslip, $organization)
        );

        $payslip->update([
            'emailed_at' => now(),
            'email_count' => $payslip->email_count + 1,
        ]);

        $this->auditLogger->log($payslip, 'payslip_emailed', [
            'payslip_number' => $payslip->payslip_number,
            'recipient' => $email,
            'email_count' => $payslip->email_count,
        ], $actor);

        event(PayslipEmailed::forModel($payslip->fresh(), [
            'actor_id' => $actor?->id,
            'employee_id' => $payslip->employee_id,
            'recipient' => $email,
        ]));
    }

    public function resendPayslipEmail(Payslip $payslip, User $actor): void
    {
        $this->sendPayslipEmail($payslip, $actor);
    }

    public function recordDownload(Payslip $payslip, User $actor): void
    {
        $this->auditLogger->log($payslip, 'payslip_downloaded', [
            'payslip_number' => $payslip->payslip_number,
            'employee_id' => $payslip->employee_id,
        ], $actor);
    }

    /** @return Collection<int, Payslip> */
    public function listPayslips(?array $filters = []): Collection
    {
        $query = Payslip::query()
            ->with(['employee', 'payrollRun.period'])
            ->orderByDesc('generated_at');

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (! empty($filters['payroll_run_id'])) {
            $query->where('payroll_run_id', $filters['payroll_run_id']);
        }
        if (! empty($filters['year'])) {
            $query->whereYear('generated_at', (int) $filters['year']);
        }
        if (! empty($filters['month'])) {
            $query->whereMonth('generated_at', (int) $filters['month']);
        }
        if (! empty($filters['period_id'])) {
            $query->whereHas('payrollRun', fn ($q) => $q->where('payroll_period_id', $filters['period_id']));
        }

        return $query->limit($filters['limit'] ?? 200)->get();
    }

    /** @return Collection<int, Payslip> */
    public function employeePayslips(Employee $employee, ?array $filters = []): Collection
    {
        $filters['employee_id'] = $employee->id;

        return $this->listPayslips($filters);
    }

    public function publicationForRun(PayrollRun $run): ?PayrollPublication
    {
        return PayrollPublication::query()
            ->where('payroll_run_id', $run->id)
            ->with(['publishedBy', 'payslips'])
            ->first();
    }

    /** @return Collection<int, PayrollApproval> */
    public function approvalsForRun(PayrollRun $run): Collection
    {
        return PayrollApproval::query()
            ->where('payroll_run_id', $run->id)
            ->with('approvedBy')
            ->orderBy('approved_at')
            ->get();
    }

    protected function nextPayslipNumber(PayrollRun $run, PayrollResult $result): string
    {
        $period = $run->period;
        $ym = $period?->start_date?->format('Ym') ?? now()->format('Ym');

        return sprintf('PS-%s-%d-%d', $ym, $run->id, $result->employee_id);
    }

    protected function notifyEmployeePublished(Payslip $payslip): void
    {
        $payslip->loadMissing('employee.user');
        $userId = $payslip->employee?->user_id;

        if (! $userId) {
            return;
        }

        try {
            $this->notificationService->send(
                $payslip->organization_id,
                $userId,
                __('Payslip available'),
                __('Your payslip :number is ready to view.', ['number' => $payslip->payslip_number]),
                '/hrms/ess/payroll/payslips/'.$payslip->id
            );
        } catch (\Throwable) {
            // In-app notification is best-effort; publication must not fail.
        }
    }
}
