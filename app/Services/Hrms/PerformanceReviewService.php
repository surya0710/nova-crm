<?php

namespace App\Services\Hrms;

use App\Events\PerformanceReviewAssigned;
use App\Events\PerformanceReviewClosed;
use App\Events\PerformanceReviewReviewed;
use App\Events\PerformanceReviewStarted;
use App\Events\PerformanceReviewSubmitted;
use App\Models\Employee;
use App\Models\Goal;
use App\Models\PerformanceConfiguration;
use App\Models\PerformanceCycle;
use App\Models\PerformanceRatingScale;
use App\Models\PerformanceReview;
use App\Models\PerformanceReviewAssignment;
use App\Models\PerformanceReviewCompetencyEvaluation;
use App\Models\PerformanceReviewGoalEvaluation;
use App\Models\PerformanceReviewTemplate;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PerformanceReviewService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
    ) {}

    // -------------------------------------------------------------------------
    // Assignments
    // -------------------------------------------------------------------------

    public function createAssignment(array $data, User $actor): PerformanceReviewAssignment
    {
        return DB::transaction(function () use ($data, $actor): PerformanceReviewAssignment {
            $this->assertConfigKey('performance_review_types', $data['review_type']);
            $status = $data['status'] ?? 'assigned';
            $this->assertConfigKey('performance_review_assignment_statuses', $status);

            $cycle = $this->assertOrgCycle((int) $data['performance_cycle_id']);
            $employee = $this->assertOrgEmployee((int) $data['employee_id']);
            $template = $this->assertOrgTemplate((int) $data['review_template_id']);

            $reviewType = $data['review_type'];
            $primaryReviewerId = isset($data['primary_reviewer_id']) ? (int) $data['primary_reviewer_id'] : null;

            if ($reviewType === 'self') {
                $primaryReviewerId = $employee->id;
            } elseif ($primaryReviewerId) {
                $this->assertOrgEmployee($primaryReviewerId);
            } else {
                $primaryReviewerId = $employee->reporting_manager_id
                    ? $this->assertOrgEmployee((int) $employee->reporting_manager_id)->id
                    : null;
            }

            if ($reviewType === 'manager' && ! $primaryReviewerId) {
                throw ValidationException::withMessages([
                    'primary_reviewer_id' => 'Manager reviews require a primary reviewer.',
                ]);
            }

            $duplicate = PerformanceReviewAssignment::query()
                ->where('performance_cycle_id', $cycle->id)
                ->where('employee_id', $employee->id)
                ->where('review_type', $reviewType)
                ->whereNotIn('status', ['cancelled'])
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'employee_id' => 'An active review assignment already exists for this employee, cycle, and review type.',
                ]);
            }

            $assignment = PerformanceReviewAssignment::query()->create([
                'performance_cycle_id' => $cycle->id,
                'employee_id' => $employee->id,
                'review_template_id' => $template->id,
                'primary_reviewer_id' => $primaryReviewerId,
                'due_date' => $data['due_date'] ?? null,
                'review_type' => $reviewType,
                'status' => 'planned',
                'assigned_by' => $actor->id,
            ]);

            $this->auditLogger->log($assignment, 'performance_review_assignment_created', [
                'employee_id' => $assignment->employee_id,
                'review_type' => $assignment->review_type,
                'cycle_id' => $assignment->performance_cycle_id,
            ], $actor);

            if ($status !== 'planned') {
                $assignment = $this->activateAssignmentWithinTransaction($assignment, $actor);
            }

            return $assignment->fresh(['review', 'employee', 'primaryReviewer', 'template', 'cycle']);
        });
    }

    public function activateAssignment(PerformanceReviewAssignment $assignment, User $actor): PerformanceReviewAssignment
    {
        return DB::transaction(function () use ($assignment, $actor): PerformanceReviewAssignment {
            return $this->activateAssignmentWithinTransaction($assignment, $actor);
        });
    }

    protected function activateAssignmentWithinTransaction(PerformanceReviewAssignment $assignment, User $actor): PerformanceReviewAssignment
    {
        $assignment->refresh();

        if ($assignment->isImmutable()) {
            throw ValidationException::withMessages([
                'assignment' => 'Closed or cancelled assignments cannot be activated.',
            ]);
        }

        if ($assignment->status !== 'planned' && $assignment->review) {
            return $assignment->fresh(['review']);
        }

        if ($assignment->status !== 'planned') {
            throw ValidationException::withMessages([
                'assignment' => 'Only planned assignments can be activated.',
            ]);
        }

        $review = $this->initializeReview($assignment, $actor);

        $assignment->update([
            'status' => 'assigned',
            'assigned_at' => Carbon::now(),
            'assigned_by' => $assignment->assigned_by ?? $actor->id,
        ]);

        $this->auditLogger->log($assignment, 'performance_review_assigned', [
            'review_id' => $review->id,
            'review_type' => $assignment->review_type,
        ], $actor);

        event(PerformanceReviewAssigned::forModel($assignment, [
            'actor_id' => $actor->id,
            'review_id' => $review->id,
        ]));

        return $assignment->fresh(['review']);
    }

    public function cancelAssignment(PerformanceReviewAssignment $assignment, User $actor): PerformanceReviewAssignment
    {
        return DB::transaction(function () use ($assignment, $actor): PerformanceReviewAssignment {
            if ($assignment->isImmutable()) {
                throw ValidationException::withMessages([
                    'assignment' => 'Closed or cancelled assignments cannot be changed.',
                ]);
            }

            if (in_array($assignment->status, ['submitted', 'reviewed'], true)) {
                throw ValidationException::withMessages([
                    'assignment' => 'Submitted or reviewed assignments cannot be cancelled. Close the review instead.',
                ]);
            }

            $assignment->update([
                'status' => 'cancelled',
                'cancelled_at' => Carbon::now(),
            ]);

            if ($assignment->review && $assignment->review->isEditable()) {
                $assignment->review->update(['status' => 'closed', 'closed_at' => Carbon::now()]);
            }

            $this->auditLogger->log($assignment, 'performance_review_assignment_cancelled', [
                'review_type' => $assignment->review_type,
            ], $actor);

            return $assignment->fresh(['review']);
        });
    }

    // -------------------------------------------------------------------------
    // Review lifecycle
    // -------------------------------------------------------------------------

    public function startReview(PerformanceReview $review, User $actor): PerformanceReview
    {
        return DB::transaction(function () use ($review, $actor): PerformanceReview {
            $this->assertReviewEditable($review);

            if (! in_array($review->status, ['draft', 'in_progress'], true)) {
                throw ValidationException::withMessages([
                    'review' => 'Only draft reviews can be started.',
                ]);
            }

            if ($review->status === 'in_progress') {
                return $review->fresh();
            }

            $review->update([
                'status' => 'in_progress',
                'started_at' => Carbon::now(),
            ]);

            $review->assignment?->update(['status' => 'in_progress']);

            $this->auditLogger->log($review, 'performance_review_started', [
                'review_type' => $review->review_type,
            ], $actor);

            event(PerformanceReviewStarted::forModel($review, ['actor_id' => $actor->id]));

            return $review->fresh(['competencyEvaluations', 'goalEvaluations', 'assignment']);
        });
    }

    public function saveDraft(PerformanceReview $review, array $data, User $actor): PerformanceReview
    {
        return DB::transaction(function () use ($review, $data, $actor): PerformanceReview {
            $this->applyDraftContent($review, $data, $actor, audit: true);

            return $review->fresh(['competencyEvaluations', 'goalEvaluations', 'assignment']);
        });
    }

    public function submitReview(PerformanceReview $review, array $data, User $actor): PerformanceReview
    {
        return DB::transaction(function () use ($review, $data, $actor): PerformanceReview {
            $this->assertReviewEditable($review);

            if ($data !== []) {
                $this->applyDraftContent($review, $data, $actor, audit: false);
                $review->refresh();
            }

            $this->assertSubmissionReady($review);

            $review->update([
                'status' => 'submitted',
                'submitted_at' => Carbon::now(),
                'started_at' => $review->started_at ?? Carbon::now(),
            ]);

            $review->assignment?->update(['status' => 'submitted']);

            $this->auditLogger->log($review, 'performance_review_submitted', [
                'review_type' => $review->review_type,
            ], $actor);

            event(PerformanceReviewSubmitted::forModel($review, ['actor_id' => $actor->id]));

            return $review->fresh(['competencyEvaluations', 'goalEvaluations', 'assignment']);
        });
    }

    protected function applyDraftContent(PerformanceReview $review, array $data, User $actor, bool $audit): void
    {
        $this->assertReviewEditable($review);

        if ($review->status === 'draft' && ($review->review_type === 'manager' || ($data['start'] ?? false) || $review->review_type === 'self')) {
            // Self reviews stay draft until submit unless explicitly started; manager drafts move in progress.
            if ($review->review_type === 'manager' || ($data['start'] ?? false)) {
                $review->update([
                    'status' => 'in_progress',
                    'started_at' => $review->started_at ?? Carbon::now(),
                ]);
                $review->assignment?->update(['status' => 'in_progress']);

                if ($review->wasChanged('status')) {
                    $this->auditLogger->log($review, 'performance_review_started', [
                        'review_type' => $review->review_type,
                        'via' => 'draft',
                    ], $actor);
                    event(PerformanceReviewStarted::forModel($review, ['actor_id' => $actor->id]));
                }
            }
        }

        $review->update([
            'overall_comments' => array_key_exists('overall_comments', $data) ? $data['overall_comments'] : $review->overall_comments,
            'development_notes' => array_key_exists('development_notes', $data) ? $data['development_notes'] : $review->development_notes,
            'strengths' => array_key_exists('strengths', $data) ? $data['strengths'] : $review->strengths,
            'improvement_areas' => array_key_exists('improvement_areas', $data) ? $data['improvement_areas'] : $review->improvement_areas,
        ]);

        if (! empty($data['competency_evaluations']) && is_array($data['competency_evaluations'])) {
            $this->persistCompetencyEvaluations($review, $data['competency_evaluations']);
        }

        if (! empty($data['goal_evaluations']) && is_array($data['goal_evaluations'])) {
            $this->persistGoalEvaluationComments($review, $data['goal_evaluations']);
        }

        if ($audit) {
            $this->auditLogger->log($review, 'performance_review_draft_saved', [
                'review_type' => $review->review_type,
                'status' => $review->fresh()->status,
            ], $actor);
        }
    }

    public function markReviewed(PerformanceReview $review, User $actor): PerformanceReview
    {
        return DB::transaction(function () use ($review, $actor): PerformanceReview {
            if ($review->status !== 'submitted') {
                throw ValidationException::withMessages([
                    'review' => 'Only submitted reviews can be marked as reviewed.',
                ]);
            }

            $review->update([
                'status' => 'reviewed',
                'reviewed_at' => Carbon::now(),
            ]);

            $review->assignment?->update(['status' => 'reviewed']);

            $this->auditLogger->log($review, 'performance_review_reviewed', [
                'review_type' => $review->review_type,
            ], $actor);

            event(PerformanceReviewReviewed::forModel($review, ['actor_id' => $actor->id]));

            return $review->fresh(['assignment']);
        });
    }

    public function closeReview(PerformanceReview $review, User $actor): PerformanceReview
    {
        return DB::transaction(function () use ($review, $actor): PerformanceReview {
            if (! in_array($review->status, ['submitted', 'reviewed'], true)) {
                throw ValidationException::withMessages([
                    'review' => 'Only submitted or reviewed reviews can be closed.',
                ]);
            }

            $review->update([
                'status' => 'closed',
                'closed_at' => Carbon::now(),
                'reviewed_at' => $review->reviewed_at ?? Carbon::now(),
            ]);

            $review->assignment?->update(['status' => 'closed']);

            $this->auditLogger->log($review, 'performance_review_closed', [
                'review_type' => $review->review_type,
            ], $actor);

            event(PerformanceReviewClosed::forModel($review, ['actor_id' => $actor->id]));

            return $review->fresh(['assignment']);
        });
    }

    // -------------------------------------------------------------------------
    // Resolution helpers
    // -------------------------------------------------------------------------

    public function resolveActiveReviewsForEmployee(int $employeeId): Collection
    {
        return PerformanceReview::query()
            ->where('employee_id', $employeeId)
            ->whereIn('status', ['draft', 'in_progress', 'submitted'])
            ->with(['assignment', 'cycle', 'template'])
            ->orderByDesc('id')
            ->get();
    }

    public function resolveTeamReviewsForManager(int $managerEmployeeId): Collection
    {
        return PerformanceReview::query()
            ->where(function ($query) use ($managerEmployeeId) {
                $query->where('reviewer_id', $managerEmployeeId)
                    ->orWhereHas('assignment', fn ($q) => $q->where('primary_reviewer_id', $managerEmployeeId));
            })
            ->where('review_type', 'manager')
            ->whereNotIn('status', ['closed'])
            ->with(['employee', 'assignment', 'cycle'])
            ->orderByDesc('id')
            ->get();
    }

    // -------------------------------------------------------------------------
    // Snapshot + evaluation initialization
    // -------------------------------------------------------------------------

    protected function initializeReview(PerformanceReviewAssignment $assignment, User $actor): PerformanceReview
    {
        $template = PerformanceReviewTemplate::query()
            ->with(['sections', 'templateCompetencies.competency', 'templateCompetencies.section'])
            ->findOrFail($assignment->review_template_id);

        $snapshot = $this->buildSnapshot($assignment, $template);
        $hash = hash('sha256', json_encode($snapshot));

        $reviewerId = $assignment->review_type === 'self'
            ? $assignment->employee_id
            : $assignment->primary_reviewer_id;

        $review = PerformanceReview::query()->create([
            'review_assignment_id' => $assignment->id,
            'performance_cycle_id' => $assignment->performance_cycle_id,
            'employee_id' => $assignment->employee_id,
            'review_template_id' => $assignment->review_template_id,
            'reviewer_id' => $reviewerId,
            'review_type' => $assignment->review_type,
            'status' => 'draft',
            'snapshot' => $snapshot,
            'snapshot_hash' => $hash,
        ]);

        foreach ($snapshot['competencies'] as $index => $item) {
            PerformanceReviewCompetencyEvaluation::query()->create([
                'performance_review_id' => $review->id,
                'competency_id' => $item['competency_id'],
                'competency_name' => $item['name'],
                'competency_code' => $item['code'],
                'section_name' => $item['section_name'],
                'weightage' => $item['weightage'],
                'sort_order' => $item['sort_order'] ?? $index,
            ]);
        }

        foreach ($snapshot['goals'] as $item) {
            PerformanceReviewGoalEvaluation::query()->create([
                'performance_review_id' => $review->id,
                'goal_id' => $item['goal_id'],
                'goal_title' => $item['title'],
                'goal_description' => $item['description'],
                'measurement_type' => $item['measurement_type'],
                'target_value' => $item['target_value'],
                'current_value' => $item['current_value'],
                'achievement_percentage' => $item['achievement_percentage'],
                'weight' => $item['weight'],
                'completion_status' => $item['completion_status'],
                'kpi_name' => $item['kpi_name'],
                'kpi_code' => $item['kpi_code'],
                'kpi_value' => $item['kpi_value'],
            ]);
        }

        $this->auditLogger->log($review, 'performance_review_initialized', [
            'assignment_id' => $assignment->id,
            'snapshot_hash' => $hash,
            'competency_count' => count($snapshot['competencies']),
            'goal_count' => count($snapshot['goals']),
        ], $actor);

        return $review;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildSnapshot(PerformanceReviewAssignment $assignment, PerformanceReviewTemplate $template): array
    {
        $config = PerformanceConfiguration::query()->first();
        $ratingScale = null;

        if ($config?->rating_scale_id) {
            $ratingScale = PerformanceRatingScale::query()
                ->with('levels')
                ->find($config->rating_scale_id);
        }

        if (! $ratingScale) {
            $ratingScale = PerformanceRatingScale::query()
                ->with('levels')
                ->where('is_default', true)
                ->where('is_active', true)
                ->first();
        }

        $competencies = $template->templateCompetencies->map(function ($row) {
            return [
                'competency_id' => $row->competency_id,
                'name' => $row->competency?->name ?? 'Unknown',
                'code' => $row->competency?->code,
                'section_name' => $row->section?->name,
                'weightage' => (float) $row->weightage,
                'sort_order' => (int) $row->sort_order,
            ];
        })->values()->all();

        $goalStatuses = config('hrms.performance_review.goal_snapshot_statuses', [
            'assigned', 'in_progress', 'completed',
        ]);

        $goals = Goal::query()
            ->with('kpi')
            ->where('employee_id', $assignment->employee_id)
            ->where('assignee_type', 'employee')
            ->where('performance_cycle_id', $assignment->performance_cycle_id)
            ->whereIn('status', $goalStatuses)
            ->orderBy('id')
            ->get()
            ->map(function (Goal $goal) {
                return [
                    'goal_id' => $goal->id,
                    'title' => $goal->title,
                    'description' => $goal->description,
                    'measurement_type' => $goal->measurement_type,
                    'target_value' => $goal->target_value !== null ? (float) $goal->target_value : null,
                    'current_value' => $goal->current_value !== null ? (float) $goal->current_value : null,
                    'achievement_percentage' => (float) $goal->achievement_percentage,
                    'weight' => (float) $goal->weight,
                    'completion_status' => $goal->status,
                    'kpi_id' => $goal->kpi_id,
                    'kpi_name' => $goal->kpi?->name,
                    'kpi_code' => $goal->kpi?->code,
                    'kpi_value' => $goal->current_value !== null ? (float) $goal->current_value : null,
                ];
            })->values()->all();

        return [
            'engine_version' => '10.4.3',
            'captured_at' => Carbon::now()->toIso8601String(),
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'code' => $template->code,
                'description' => $template->description,
                'instructions' => $template->instructions,
                'sections' => $template->sections->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'instructions' => $s->instructions,
                    'weightage' => (float) $s->weightage,
                    'sort_order' => (int) $s->sort_order,
                ])->values()->all(),
            ],
            'rating_scale' => $ratingScale ? [
                'id' => $ratingScale->id,
                'name' => $ratingScale->name,
                'code' => $ratingScale->code,
                'levels' => $ratingScale->levels->map(fn ($level) => [
                    'value' => (float) $level->value,
                    'label' => $level->label,
                    'description' => $level->description,
                    'sort_order' => (int) $level->sort_order,
                ])->values()->all(),
            ] : null,
            'configuration' => $config ? [
                'goal_weighting' => (float) $config->goal_weighting,
                'competency_weighting' => (float) $config->competency_weighting,
                'review_visibility' => $config->review_visibility,
            ] : null,
            'competencies' => $competencies,
            'goals' => $goals,
            'kpi_values' => collect($goals)
                ->filter(fn ($g) => ! empty($g['kpi_id']))
                ->map(fn ($g) => [
                    'kpi_id' => $g['kpi_id'],
                    'kpi_name' => $g['kpi_name'],
                    'kpi_code' => $g['kpi_code'],
                    'value' => $g['kpi_value'],
                    'goal_id' => $g['goal_id'],
                ])->values()->all(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function persistCompetencyEvaluations(PerformanceReview $review, array $rows): void
    {
        $allowedRatings = collect($review->snapshot['rating_scale']['levels'] ?? [])
            ->pluck('value')
            ->map(fn ($v) => (float) $v)
            ->all();

        foreach ($rows as $row) {
            $evaluation = PerformanceReviewCompetencyEvaluation::query()
                ->where('performance_review_id', $review->id)
                ->where('id', (int) ($row['id'] ?? 0))
                ->first();

            if (! $evaluation) {
                continue;
            }

            $rating = array_key_exists('rating', $row) && $row['rating'] !== null && $row['rating'] !== ''
                ? (float) $row['rating']
                : null;

            if ($rating !== null && $allowedRatings !== []) {
                $matchesScale = collect($allowedRatings)->contains(
                    fn (float $allowed) => abs($allowed - $rating) < 0.0001
                );

                if (! $matchesScale) {
                    throw ValidationException::withMessages([
                        'competency_evaluations' => "Invalid rating value {$rating} for competency evaluation.",
                    ]);
                }
            }

            $evaluation->update([
                'rating' => $rating,
                'comments' => array_key_exists('comments', $row) ? $row['comments'] : $evaluation->comments,
                'reviewer_notes' => array_key_exists('reviewer_notes', $row) ? $row['reviewer_notes'] : $evaluation->reviewer_notes,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function persistGoalEvaluationComments(PerformanceReview $review, array $rows): void
    {
        foreach ($rows as $row) {
            $evaluation = PerformanceReviewGoalEvaluation::query()
                ->where('performance_review_id', $review->id)
                ->where('id', (int) ($row['id'] ?? 0))
                ->first();

            if (! $evaluation) {
                continue;
            }

            // Snapshot fields stay frozen; only evaluation commentary/rating may change.
            $evaluation->update([
                'comments' => array_key_exists('comments', $row) ? $row['comments'] : $evaluation->comments,
                'rating' => array_key_exists('rating', $row) && $row['rating'] !== null && $row['rating'] !== ''
                    ? (float) $row['rating']
                    : (array_key_exists('rating', $row) ? null : $evaluation->rating),
            ]);
        }
    }

    protected function assertSubmissionReady(PerformanceReview $review): void
    {
        $review->loadMissing(['competencyEvaluations']);

        if ($review->competencyEvaluations->isNotEmpty()) {
            $missing = $review->competencyEvaluations->first(fn ($e) => $e->rating === null);
            if ($missing) {
                throw ValidationException::withMessages([
                    'competency_evaluations' => 'All competency ratings must be completed before submission.',
                ]);
            }
        }
    }

    protected function assertReviewEditable(PerformanceReview $review): void
    {
        if (! $review->isEditable()) {
            throw ValidationException::withMessages([
                'review' => 'Submitted, reviewed, or closed reviews cannot be edited.',
            ]);
        }

        $assignment = $review->assignment;
        if ($assignment && $assignment->isImmutable()) {
            throw ValidationException::withMessages([
                'review' => 'Reviews on closed or cancelled assignments cannot be edited.',
            ]);
        }
    }

    protected function assertConfigKey(string $configKey, string $value): void
    {
        $allowed = array_keys(config("hrms.{$configKey}", []));
        if (! in_array($value, $allowed, true)) {
            throw ValidationException::withMessages([
                $configKey => "Invalid value [{$value}] for {$configKey}.",
            ]);
        }
    }

    protected function assertOrgCycle(int $id): PerformanceCycle
    {
        $cycle = PerformanceCycle::query()->find($id);
        if (! $cycle) {
            throw ValidationException::withMessages(['performance_cycle_id' => 'Performance cycle not found.']);
        }

        return $cycle;
    }

    protected function assertOrgEmployee(int $id): Employee
    {
        $employee = Employee::query()->find($id);
        if (! $employee) {
            throw ValidationException::withMessages(['employee_id' => 'Employee not found.']);
        }

        return $employee;
    }

    protected function assertOrgTemplate(int $id): PerformanceReviewTemplate
    {
        $template = PerformanceReviewTemplate::query()->find($id);
        if (! $template || ! $template->is_active) {
            throw ValidationException::withMessages(['review_template_id' => 'Active review template not found.']);
        }

        return $template;
    }
}
