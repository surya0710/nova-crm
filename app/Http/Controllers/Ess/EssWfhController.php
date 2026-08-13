<?php

namespace App\Http\Controllers\Ess;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ess\EssCancelWfhRequestRequest;
use App\Http\Requests\Ess\EssStoreWfhRequestRequest;
use App\Models\WfhRequest;
use App\Services\Hrms\EssContext;
use App\Services\Hrms\WfhPolicyService;
use App\Services\Hrms\WfhRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EssWfhController extends Controller
{
    public function __construct(
        protected EssContext $essContext,
        protected WfhRequestService $wfhRequestService,
        protected WfhPolicyService $wfhPolicyService,
    ) {}

    public function index(): View
    {
        $employee = $this->essContext->requireEmployee();
        $this->authorize('viewAny', WfhRequest::class);

        $todayResolution = $this->wfhPolicyService->resolveForDate($employee, now());
        $orgPolicy = $this->wfhPolicyService->resolveOrganizationPolicy($employee);

        $upcoming = WfhRequest::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('end_date', '>=', now()->toDateString())
            ->orderBy('start_date')
            ->limit(10)
            ->get();

        return view('ess.wfh.index', [
            'employee' => $employee,
            'requests' => WfhRequest::query()
                ->where('employee_id', $employee->id)
                ->with('approvalSteps')
                ->latest('submitted_at')
                ->paginate(15),
            'assignments' => $this->wfhPolicyService->assignmentsForEmployee($employee),
            'upcoming' => $upcoming,
            'todayResolution' => $todayResolution,
            'orgPolicy' => $orgPolicy,
            'statuses' => config('hrms.wfh_request_statuses', []),
            'stepStatuses' => config('hrms.wfh_approval_step_statuses', []),
            'weekdays' => config('hrms.wfh_weekdays', []),
        ]);
    }

    public function store(EssStoreWfhRequestRequest $request): RedirectResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        $this->wfhRequestService->submit($employee, $request->validated(), $request->user(), true);

        return redirect()
            ->route('ess.wfh.index')
            ->with('status', 'ess-wfh-requested');
    }

    public function destroy(WfhRequest $wfhRequest): RedirectResponse
    {
        $employee = $this->essContext->requireEmployee();
        abort_unless($wfhRequest->employee_id === $employee->id, 404);
        $this->authorize('withdrawOwn', $wfhRequest);
        $this->wfhRequestService->withdraw($wfhRequest, request()->user());

        return redirect()
            ->route('ess.wfh.index')
            ->with('status', 'ess-wfh-withdrawn');
    }

    public function cancel(EssCancelWfhRequestRequest $request, WfhRequest $wfhRequest): RedirectResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        abort_unless($wfhRequest->employee_id === $employee->id, 404);
        $this->authorize('cancel', $wfhRequest);
        $this->wfhRequestService->cancel($wfhRequest, $request->user(), $request->validated('remarks'));

        return redirect()
            ->route('ess.wfh.index')
            ->with('status', 'ess-wfh-cancelled');
    }
}
