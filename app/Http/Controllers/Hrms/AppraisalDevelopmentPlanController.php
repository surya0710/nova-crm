<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\UpdateAppraisalDevelopmentPlanRequest;
use App\Models\AppraisalSession;
use App\Models\EmployeeAppraisal;
use App\Services\Hrms\AppraisalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppraisalDevelopmentPlanController extends Controller
{
    public function __construct(protected AppraisalService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', EmployeeAppraisal::class);

        $query = EmployeeAppraisal::query()
            ->with(['employee', 'session', 'developmentPlan'])
            ->whereHas('developmentPlan')
            ->latest();

        if ($request->filled('session_id')) {
            $query->where('appraisal_session_id', $request->integer('session_id'));
        }

        return view('hrms.performance.development-plans.index', [
            'appraisals' => $query->paginate(20)->withQueryString(),
            'sessions' => AppraisalSession::query()->orderByDesc('start_date')->get(),
        ]);
    }

    public function update(UpdateAppraisalDevelopmentPlanRequest $request, EmployeeAppraisal $appraisal): RedirectResponse
    {
        $this->service->updateDevelopmentPlan($appraisal, $request->validated(), $request->user());

        return redirect()->route('hrms.performance.appraisals.show', $appraisal)
            ->with('status', 'hrms-development-plan-updated');
    }
}
