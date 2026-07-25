<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CreatePerformanceReviewAssignmentRequest;
use App\Models\Employee;
use App\Models\PerformanceCycle;
use App\Models\PerformanceReviewAssignment;
use App\Models\PerformanceReviewTemplate;
use App\Services\Hrms\PerformanceReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerformanceReviewAssignmentController extends Controller
{
    public function __construct(protected PerformanceReviewService $service)
    {
        $this->authorizeResource(PerformanceReviewAssignment::class, 'assignment');
    }

    public function index(Request $request): View
    {
        $query = PerformanceReviewAssignment::query()
            ->with(['employee', 'primaryReviewer', 'cycle', 'template', 'review'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('review_type')) {
            $query->where('review_type', $request->string('review_type'));
        }
        if ($request->filled('cycle_id')) {
            $query->where('performance_cycle_id', $request->integer('cycle_id'));
        }

        return view('hrms.performance.review-assignments.index', [
            'assignments' => $query->paginate(20)->withQueryString(),
            'cycles' => PerformanceCycle::query()->orderByDesc('start_date')->get(),
            'employees' => Employee::query()->orderBy('first_name')->limit(200)->get(),
            'templates' => PerformanceReviewTemplate::query()->where('is_active', true)->orderBy('name')->get(),
            'reviewTypes' => config('hrms.performance_review_types', []),
            'statuses' => config('hrms.performance_review_assignment_statuses', []),
        ]);
    }

    public function store(CreatePerformanceReviewAssignmentRequest $request): RedirectResponse
    {
        $this->service->createAssignment($request->validated(), $request->user());

        return redirect()->route('hrms.performance.review-assignments.index')
            ->with('status', 'hrms-performance-review-assignment-created');
    }

    public function show(PerformanceReviewAssignment $assignment): View
    {
        $assignment->load([
            'employee', 'primaryReviewer', 'cycle', 'template',
            'review.competencyEvaluations', 'review.goalEvaluations',
        ]);

        return view('hrms.performance.review-assignments.show', [
            'assignment' => $assignment,
            'statuses' => config('hrms.performance_review_assignment_statuses', []),
            'reviewTypes' => config('hrms.performance_review_types', []),
            'reviewStatuses' => config('hrms.performance_review_statuses', []),
        ]);
    }

    public function destroy(PerformanceReviewAssignment $assignment): RedirectResponse
    {
        $this->service->cancelAssignment($assignment, request()->user());

        return redirect()->route('hrms.performance.review-assignments.index')
            ->with('status', 'hrms-performance-review-assignment-cancelled');
    }

    public function activate(PerformanceReviewAssignment $assignment): RedirectResponse
    {
        $this->authorize('activate', $assignment);
        $this->service->activateAssignment($assignment, request()->user());

        return redirect()->route('hrms.performance.review-assignments.show', $assignment)
            ->with('status', 'hrms-performance-review-assignment-activated');
    }
}
