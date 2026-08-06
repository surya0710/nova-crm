<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\ApplyCalibrationAdjustmentsRequest;
use App\Http\Requests\Hrms\CreateAppraisalCalibrationRequest;
use App\Models\AppraisalCalibration;
use App\Models\AppraisalSession;
use App\Models\EmployeeAppraisal;
use App\Services\Hrms\AppraisalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppraisalCalibrationController extends Controller
{
    public function __construct(protected AppraisalService $service)
    {
        $this->authorizeResource(AppraisalCalibration::class, 'calibration');
    }

    public function index(Request $request): View
    {
        $query = AppraisalCalibration::query()->with('session')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('hrms.performance.calibration.index', [
            'calibrations' => $query->paginate(20)->withQueryString(),
            'sessions' => AppraisalSession::query()->orderByDesc('start_date')->get(),
            'statuses' => config('hrms.appraisal_calibration_statuses', []),
        ]);
    }

    public function store(CreateAppraisalCalibrationRequest $request): RedirectResponse
    {
        $session = AppraisalSession::query()->findOrFail($request->integer('appraisal_session_id'));
        $calibration = $this->service->createCalibration($session, $request->validated(), $request->user());

        return redirect()->route('hrms.performance.calibration.show', $calibration)
            ->with('status', 'hrms-calibration-created');
    }

    public function show(AppraisalCalibration $calibration): View
    {
        $calibration->load(['session.employeeAppraisals.employee', 'approver', 'creator']);

        $appraisals = EmployeeAppraisal::query()
            ->where('appraisal_session_id', $calibration->appraisal_session_id)
            ->with('employee')
            ->get();

        return view('hrms.performance.calibration.show', [
            'calibration' => $calibration,
            'appraisals' => $appraisals,
            'statuses' => config('hrms.appraisal_calibration_statuses', []),
        ]);
    }

    public function applyAdjustments(ApplyCalibrationAdjustmentsRequest $request, AppraisalCalibration $calibration): RedirectResponse
    {
        $this->service->applyCalibrationAdjustments($calibration, $request->validated('adjustments'), $request->user());

        return redirect()->route('hrms.performance.calibration.show', $calibration)
            ->with('status', 'hrms-calibration-adjustments-applied');
    }

    public function approve(AppraisalCalibration $calibration): RedirectResponse
    {
        $this->authorize('approve', $calibration);
        $this->service->approveCalibration($calibration, request()->only('session_comments'), request()->user());

        return redirect()->route('hrms.performance.calibration.show', $calibration)
            ->with('status', 'hrms-calibration-approved');
    }
}
