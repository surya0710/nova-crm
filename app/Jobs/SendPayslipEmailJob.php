<?php

namespace App\Jobs;

use App\Models\Payslip;
use App\Models\User;
use App\Services\Hrms\PayrollPublicationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendPayslipEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300];

    public int $timeout = 120;

    public function __construct(
        public int $payslipId,
        public ?int $actorId = null,
    ) {
        $this->onQueue('mail');
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('payslip-email-'.$this->payslipId))
                ->releaseAfter(30)
                ->expireAfter($this->timeout + 60),
        ];
    }

    public function handle(PayrollPublicationService $service): void
    {
        $payslip = Payslip::query()->find($this->payslipId);
        if (! $payslip || $payslip->emailed_at !== null) {
            return;
        }

        $actor = $this->actorId ? User::query()->find($this->actorId) : null;
        $service->sendPayslipEmail($payslip, $actor);
    }

    public function failed(?Throwable $exception): void
    {
        Log::critical('payslip.email.job_failed', [
            'payslip_id' => $this->payslipId,
            'actor_id' => $this->actorId,
            'reason' => $exception?->getMessage() ?? 'Payslip email queue job failed.',
            'exception' => $exception,
        ]);
    }
}
