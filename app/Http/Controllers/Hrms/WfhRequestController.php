<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\ApproveWfhRequestRequest;
use App\Http\Requests\Hrms\CancelWfhRequestRequest;
use App\Http\Requests\Hrms\RejectWfhRequestRequest;
use App\Http\Requests\Hrms\StoreWfhRequestRequest;
use App\Models\Employee;
use App\Models\WfhRequest;
use App\Services\Hrms\WfhPolicyService;
use App\Services\Hrms\WfhRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WfhRequestController extends Controller
{
    public function __construct(
        protected WfhRequestService $service,
        protected WfhPolicyService $wfhPolicyService,
    ) {
        $this->authorizeResource(WfhRequest::class, 'wfh_request');
    }

    public function index(Request $request): View
    {
        $query = WfhRequest::query()
            ->with(['employee'])
            ->latest('submitted_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('hrms.wfh.requests.index', [
            'requests' => $query->paginate(15)->withQueryString(),
            'employees' => Employee::query()->orderBy('first_name')->orderBy('last_name')->get(),
            'statuses' => config('hrms.wfh_request_statuses', []),
            'filterStatus' => $request->string('status')->toString(),
        ]);
    }

    public function show(WfhRequest $wfhRequest): View
    {
        return view('hrms.wfh.requests.show', [
            'wfhRequest' => $wfhRequest->load([
                'employee',
                'approvalSteps.approverEmployee',
                'approvalSteps.approverUser',
            ]),
            'statuses' => config('hrms.wfh_request_statuses', []),
            'stepStatuses' => config('hrms.wfh_approval_step_statuses', []),
            'orgPolicy' => $this->wfhPolicyService->resolveOrganizationPolicy($wfhRequest->employee),
        ]);
    }

    public function store(StoreWfhRequestRequest $request): RedirectResponse
    {
        $employee = Employee::query()->findOrFail($request->validated('employee_id'));

        $this->service->submit(
            $employee,
            $request->validated(),
            $request->user(),
            (bool) $request->boolean('submit', true),
        );

        return redirect()
            ->route('hrms.wfh.requests.index')
            ->with('status', 'hrms-wfh-request-created');
    }

    public function destroy(WfhRequest $wfhRequest): RedirectResponse
    {
        $this->service->withdraw($wfhRequest, request()->user());

        return redirect()
            ->route('hrms.wfh.requests.index')
            ->with('status', 'hrms-wfh-request-withdrawn');
    }

    public function approve(ApproveWfhRequestRequest $request, WfhRequest $wfhRequest): RedirectResponse
    {
        $this->service->approve($wfhRequest, $request->user(), $request->validated('remarks'));

        return redirect()
            ->route('hrms.wfh.requests.show', $wfhRequest)
            ->with('status', 'hrms-wfh-request-approved');
    }

    public function reject(RejectWfhRequestRequest $request, WfhRequest $wfhRequest): RedirectResponse
    {
        $this->service->reject($wfhRequest, $request->user(), $request->validated('remarks'));

        return redirect()
            ->route('hrms.wfh.requests.show', $wfhRequest)
            ->with('status', 'hrms-wfh-request-rejected');
    }

    public function cancel(CancelWfhRequestRequest $request, WfhRequest $wfhRequest): RedirectResponse
    {
        $this->service->cancel($wfhRequest, $request->user(), $request->validated('remarks'));

        return redirect()
            ->route('hrms.wfh.requests.show', $wfhRequest)
            ->with('status', 'hrms-wfh-request-cancelled');
    }

    public function approvalQueue(): View
    {
        $this->authorize('viewAny', WfhRequest::class);

        $requests = WfhRequest::query()
            ->where('status', 'pending')
            ->with(['employee', 'approvalSteps'])
            ->latest('submitted_at')
            ->paginate(15);

        return view('hrms.wfh.requests.approval-queue', [
            'requests' => $requests,
        ]);
    }
}
