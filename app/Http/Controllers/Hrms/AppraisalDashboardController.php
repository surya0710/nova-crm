<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\AppraisalCalibration;
use App\Models\AppraisalSession;
use App\Models\EmployeeAppraisal;
use App\Models\TalentMatrixEntry;
use Illuminate\View\View;

class AppraisalDashboardController extends Controller
{
    public function __invoke(): View
    {
        $this->authorize('viewAny', AppraisalSession::class);

        return view('hrms.performance.appraisals.index', [
            'sessionCount' => AppraisalSession::query()->count(),
            'appraisalCount' => EmployeeAppraisal::query()->count(),
            'calibrationCount' => AppraisalCalibration::query()->count(),
            'talentCount' => TalentMatrixEntry::query()->count(),
            'activeSessions' => AppraisalSession::query()->where('status', 'active')->latest()->limit(5)->get(),
            'recentAppraisals' => EmployeeAppraisal::query()->with('employee')->latest()->limit(10)->get(),
        ]);
    }
}
