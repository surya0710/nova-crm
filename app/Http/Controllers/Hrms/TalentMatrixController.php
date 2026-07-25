<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\ClassifyTalentMatrixRequest;
use App\Models\AppraisalSession;
use App\Models\EmployeeAppraisal;
use App\Services\Hrms\AppraisalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TalentMatrixController extends Controller
{
    public function __construct(protected AppraisalService $service) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('performance.talent.manage')
            || $request->user()->hasPermission('performance.appraisal.view'), 403);

        $sessionId = $request->integer('session_id');
        $session = $sessionId
            ? AppraisalSession::query()->findOrFail($sessionId)
            : AppraisalSession::query()->where('status', 'active')->latest()->first();

        $matrix = $session
            ? $this->service->buildTalentMatrix($session)
            : ['config' => config('hrms.appraisal.default_talent_matrix'), 'cells' => [], 'entries' => collect()];

        return view('hrms.performance.talent-matrix.index', [
            'session' => $session,
            'sessions' => AppraisalSession::query()->orderByDesc('start_date')->get(),
            'matrix' => $matrix,
        ]);
    }

    public function classify(ClassifyTalentMatrixRequest $request): RedirectResponse
    {
        $appraisal = EmployeeAppraisal::query()->findOrFail($request->integer('employee_appraisal_id'));
        $this->authorize('view', $appraisal);

        $this->service->classifyTalent($appraisal, $request->validated(), $request->user());

        return redirect()->route('hrms.performance.talent-matrix.index', [
            'session_id' => $appraisal->appraisal_session_id,
        ])->with('status', 'hrms-talent-classified');
    }
}
