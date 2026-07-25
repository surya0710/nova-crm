<?php

namespace App\Jobs;

use App\Models\Payslip;
use App\Models\User;
use App\Services\Hrms\PayrollPublicationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendPayslipEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $payslipId,
        public ?int $actorId = null,
    ) {}

    public function handle(PayrollPublicationService $service): void
    {
        $payslip = Payslip::query()->find($this->payslipId);
        if (! $payslip) {
            return;
        }

        $actor = $this->actorId ? User::query()->find($this->actorId) : null;
        $service->sendPayslipEmail($payslip, $actor);
    }
}
