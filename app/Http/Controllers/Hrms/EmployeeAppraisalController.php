<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\SaveCompensationRecommendationRequest;
use App\Http\Requests\Hrms\SavePromotionRecommendationRequest;
use App\Http\Requests\Hrms\SaveSuccessionRecommendationRequest;
use App\Http\Requests\Hrms\SubmitEmployeeAppraisalRequest;
use App\Http\Requests\Hrms\UpdateEmployeeAppraisalRequest;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeAppraisal;
use App\Services\Hrms\AppraisalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeAppraisalController extends Controller
{
    public function __construct(protected AppraisalService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', EmployeeAppraisal::class);

        $query = EmployeeAppraisal::query()->with(['employee', 'session', 'manager'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('session_id')) {
            $query->where('appraisal_session_id', $request->integer('session_id'));
        }

        return view('hrms.performance.appraisals.list', [
            'appraisals' => $query->paginate(20)->withQueryString(),
            'statuses' => config('hrms.employee_appraisal_statuses', []),
        ]);
    }

    public function show(EmployeeAppraisal $appraisal): View
    {
        $this->authorize('view', $appraisal);

        $appraisal->load([
            'employee', 'manager', 'session.cycle', 'developmentPlan',
            'recommendations.targetDesignation', 'talentMatrixEntry', 'calibration',
        ]);

        return view('hrms.performance.appraisals.show', [
            'appraisal' => $appraisal,
            'statuses' => config('hrms.employee_appraisal_statuses', []),
            'promotionLevels' => config('hrms.promotion_recommendation_levels', []),
            'readinessLevels' => config('hrms.succession_readiness_levels', []),
            'designations' => Designation::query()->orderBy('name')->get(),
        ]);
    }

    public function myAppraisal(Request $request): View
    {
        $employee = Employee::query()->where('user_id', $request->user()->id)->firstOrFail();

        $appraisals = EmployeeAppraisal::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'closed')
            ->with(['session', 'developmentPlan'])
            ->latest()
            ->paginate(10);

        return view('hrms.performance.appraisals.my', [
            'appraisals' => $appraisals,
        ]);
    }

    public function teamAppraisals(Request $request): View
    {
        $manager = Employee::query()->where('user_id', $request->user()->id)->firstOrFail();

        $appraisals = EmployeeAppraisal::query()
            ->where('manager_employee_id', $manager->id)
            ->whereNotIn('status', ['closed'])
            ->with(['employee', 'session'])
            ->latest()
            ->paginate(20);

        return view('hrms.performance.appraisals.team', [
            'appraisals' => $appraisals,
            'statuses' => config('hrms.employee_appraisal_statuses', []),
        ]);
    }

    public function update(UpdateEmployeeAppraisalRequest $request, EmployeeAppraisal $appraisal): RedirectResponse
    {
        $this->service->updateAppraisal($appraisal, $request->validated(), $request->user());

        return redirect()->route('hrms.performance.appraisals.show', $appraisal)
            ->with('status', 'hrms-appraisal-updated');
    }

    public function submit(SubmitEmployeeAppraisalRequest $request, EmployeeAppraisal $appraisal): RedirectResponse
    {
        $this->service->submitAppraisal($appraisal, $request->validated(), $request->user());

        return redirect()->route('hrms.performance.appraisals.show', $appraisal)
            ->with('status', 'hrms-appraisal-submitted');
    }

    public function hrReview(UpdateEmployeeAppraisalRequest $request, EmployeeAppraisal $appraisal): RedirectResponse
    {
        $this->authorize('close', $appraisal);
        $this->service->hrReviewAppraisal($appraisal, $request->validated(), $request->user());

        return redirect()->route('hrms.performance.appraisals.show', $appraisal)
            ->with('status', 'hrms-appraisal-hr-reviewed');
    }

    public function close(EmployeeAppraisal $appraisal): RedirectResponse
    {
        $this->authorize('close', $appraisal);
        $this->service->closeAppraisal($appraisal, request()->user());

        return redirect()->route('hrms.performance.appraisals.show', $appraisal)
            ->with('status', 'hrms-appraisal-closed');
    }

    public function recalculate(EmployeeAppraisal $appraisal): RedirectResponse
    {
        $this->authorize('update', $appraisal);
        $this->service->recalculateAppraisalRating($appraisal, request()->user());

        return redirect()->route('hrms.performance.appraisals.show', $appraisal)
            ->with('status', 'hrms-appraisal-recalculated');
    }

    public function savePromotion(SavePromotionRecommendationRequest $request, EmployeeAppraisal $appraisal): RedirectResponse
    {
        $this->service->savePromotionRecommendation($appraisal, $request->validated(), $request->user());

        return redirect()->route('hrms.performance.appraisals.show', $appraisal)
            ->with('status', 'hrms-promotion-recommendation-saved');
    }

    public function saveCompensation(SaveCompensationRecommendationRequest $request, EmployeeAppraisal $appraisal): RedirectResponse
    {
        $this->service->saveCompensationRecommendation($appraisal, $request->validated(), $request->user());

        return redirect()->route('hrms.performance.appraisals.show', $appraisal)
            ->with('status', 'hrms-compensation-recommendation-saved');
    }

    public function saveSuccession(SaveSuccessionRecommendationRequest $request, EmployeeAppraisal $appraisal): RedirectResponse
    {
        $this->service->saveSuccessionRecommendation($appraisal, $request->validated(), $request->user());

        return redirect()->route('hrms.performance.appraisals.show', $appraisal)
            ->with('status', 'hrms-succession-recommendation-saved');
    }
}
