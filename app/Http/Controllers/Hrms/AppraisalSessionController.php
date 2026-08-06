<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CreateAppraisalSessionRequest;
use App\Http\Requests\Hrms\UpdateAppraisalSessionRequest;
use App\Models\AppraisalSession;
use App\Models\PerformanceCycle;
use App\Services\Hrms\AppraisalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppraisalSessionController extends Controller
{
    public function __construct(protected AppraisalService $service)
    {
        $this->authorizeResource(AppraisalSession::class, 'session');
    }

    public function index(Request $request): View
    {
        $query = AppraisalSession::query()->with('cycle')->withCount('employeeAppraisals')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('hrms.performance.appraisal-sessions.index', [
            'sessions' => $query->paginate(20)->withQueryString(),
            'cycles' => PerformanceCycle::query()->orderByDesc('start_date')->get(),
            'statuses' => config('hrms.appraisal_session_statuses', []),
            'defaultWeights' => config('hrms.appraisal.default_rating_weights', []),
        ]);
    }

    public function store(CreateAppraisalSessionRequest $request): RedirectResponse
    {
        $session = $this->service->createSession($request->validated(), $request->user());

        return redirect()->route('hrms.performance.appraisal-sessions.show', $session)
            ->with('status', 'hrms-appraisal-session-created');
    }

    public function show(AppraisalSession $session): View
    {
        $session->load(['cycle', 'employeeAppraisals.employee', 'calibrations']);

        return view('hrms.performance.appraisal-sessions.show', [
            'session' => $session,
            'statuses' => config('hrms.appraisal_session_statuses', []),
            'appraisalStatuses' => config('hrms.employee_appraisal_statuses', []),
        ]);
    }

    public function update(UpdateAppraisalSessionRequest $request, AppraisalSession $session): RedirectResponse
    {
        $this->service->updateSession($session, $request->validated(), $request->user());

        return redirect()->route('hrms.performance.appraisal-sessions.show', $session)
            ->with('status', 'hrms-appraisal-session-updated');
    }

    public function destroy(AppraisalSession $session): RedirectResponse
    {
        if (! $session->isEditable()) {
            return back()->withErrors(['session' => __('Only draft or scheduled sessions can be deleted.')]);
        }

        $session->delete();

        return redirect()->route('hrms.performance.appraisal-sessions.index')
            ->with('status', 'hrms-appraisal-session-deleted');
    }

    public function activate(AppraisalSession $session): RedirectResponse
    {
        $this->authorize('activate', $session);
        $this->service->activateSession($session, request()->user());

        return redirect()->route('hrms.performance.appraisal-sessions.show', $session)
            ->with('status', 'hrms-appraisal-session-activated');
    }

    public function close(AppraisalSession $session): RedirectResponse
    {
        $this->authorize('update', $session);
        $this->service->closeSession($session, request()->user());

        return redirect()->route('hrms.performance.appraisal-sessions.show', $session)
            ->with('status', 'hrms-appraisal-session-closed');
    }

    public function archive(AppraisalSession $session): RedirectResponse
    {
        $this->authorize('update', $session);
        $this->service->archiveSession($session, request()->user());

        return redirect()->route('hrms.performance.appraisal-sessions.show', $session)
            ->with('status', 'hrms-appraisal-session-archived');
    }

    public function generate(AppraisalSession $session): RedirectResponse
    {
        $this->authorize('generate', $session);
        $employeeIds = request()->input('employee_ids');
        $this->service->generateAppraisals($session, $employeeIds, request()->user());

        return redirect()->route('hrms.performance.appraisal-sessions.show', $session)
            ->with('status', 'hrms-appraisal-generated');
    }
}
