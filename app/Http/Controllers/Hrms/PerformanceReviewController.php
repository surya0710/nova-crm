<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\SavePerformanceReviewDraftRequest;
use App\Http\Requests\Hrms\SubmitPerformanceReviewRequest;
use App\Models\Employee;
use App\Models\PerformanceCycle;
use App\Models\PerformanceReview;
use App\Services\Hrms\PerformanceReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class PerformanceReviewController extends Controller
{
    public function __construct(protected PerformanceReviewService $service)
    {
        $this->authorizeResource(PerformanceReview::class, 'review');
    }

    public function index(Request $request): View
    {
        $query = PerformanceReview::query()
            ->with(['employee', 'reviewer', 'cycle', 'template', 'assignment'])
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

        return view('hrms.performance.reviews.index', [
            'reviews' => $query->paginate(20)->withQueryString(),
            'cycles' => PerformanceCycle::query()->orderByDesc('start_date')->get(),
            'reviewTypes' => config('hrms.performance_review_types', []),
            'statuses' => config('hrms.performance_review_statuses', []),
        ]);
    }

    public function show(PerformanceReview $review): View
    {
        $review->load([
            'employee', 'reviewer', 'cycle', 'template', 'assignment.primaryReviewer',
            'competencyEvaluations', 'goalEvaluations',
        ]);

        $ratingLevels = $review->snapshot['rating_scale']['levels'] ?? [];

        return view('hrms.performance.reviews.show', [
            'review' => $review,
            'statuses' => config('hrms.performance_review_statuses', []),
            'reviewTypes' => config('hrms.performance_review_types', []),
            'ratingLevels' => $ratingLevels,
        ]);
    }

    public function start(PerformanceReview $review): RedirectResponse
    {
        $this->authorize('update', $review);
        $this->service->startReview($review, request()->user());

        return redirect()->route('hrms.performance.reviews.show', $review)
            ->with('status', 'hrms-performance-review-started');
    }

    public function saveDraft(SavePerformanceReviewDraftRequest $request, PerformanceReview $review): RedirectResponse
    {
        $this->service->saveDraft($review, $request->validated(), $request->user());

        return redirect()->route('hrms.performance.reviews.show', $review)
            ->with('status', 'hrms-performance-review-draft-saved');
    }

    public function submit(SubmitPerformanceReviewRequest $request, PerformanceReview $review): RedirectResponse
    {
        $this->service->submitReview($review, $request->validated(), $request->user());

        return redirect()->route('hrms.performance.reviews.show', $review)
            ->with('status', 'hrms-performance-review-submitted');
    }

    public function markReviewed(PerformanceReview $review): RedirectResponse
    {
        $this->authorize('markReviewed', $review);
        $this->service->markReviewed($review, request()->user());

        return redirect()->route('hrms.performance.reviews.show', $review)
            ->with('status', 'hrms-performance-review-reviewed');
    }

    public function close(PerformanceReview $review): RedirectResponse
    {
        $this->authorize('close', $review);
        $this->service->closeReview($review, request()->user());

        return redirect()->route('hrms.performance.reviews.show', $review)
            ->with('status', 'hrms-performance-review-closed');
    }

    public function myReviews(Request $request): View
    {
        $this->authorize('viewAny', PerformanceReview::class);

        $employee = Employee::query()->where('user_id', $request->user()->id)->first();

        $reviews = PerformanceReview::query()
            ->when($employee, fn ($q) => $q->where('employee_id', $employee->id)->where('review_type', 'self'))
            ->when(! $employee, fn ($q) => $q->whereRaw('1 = 0'))
            ->with(['cycle', 'template', 'assignment'])
            ->latest()
            ->paginate(20);

        return view('hrms.performance.reviews.my', [
            'reviews' => $reviews,
            'statuses' => config('hrms.performance_review_statuses', []),
            'employee' => $employee,
        ]);
    }

    public function teamReviews(Request $request): View
    {
        $this->authorize('viewAny', PerformanceReview::class);

        $manager = Employee::query()->where('user_id', $request->user()->id)->first();
        $canManage = $request->user()->hasPermission('performance.review.manage');

        if ($canManage && ! $request->boolean('mine_only')) {
            $reviews = PerformanceReview::query()
                ->where('review_type', 'manager')
                ->with(['employee', 'cycle', 'assignment'])
                ->latest()
                ->paginate(20);
        } elseif ($manager) {
            $reviews = $this->service->resolveTeamReviewsForManager($manager->id);
            $reviews = new LengthAwarePaginator(
                $reviews->forPage(1, 20)->values(),
                $reviews->count(),
                20,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $reviews = PerformanceReview::query()->whereRaw('1 = 0')->paginate(20);
        }

        return view('hrms.performance.reviews.team', [
            'reviews' => $reviews,
            'statuses' => config('hrms.performance_review_statuses', []),
            'manager' => $manager,
        ]);
    }
}
