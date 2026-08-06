<?php

namespace App\Services\Hrms;

use App\Events\AppraisalCalibrated;
use App\Events\AppraisalClosed;
use App\Events\AppraisalGenerated;
use App\Events\AppraisalSessionCreated;
use App\Events\AppraisalSubmitted;
use App\Events\CompensationRecommended;
use App\Events\PromotionRecommended;
use App\Models\AppraisalCalibration;
use App\Models\AppraisalDevelopmentPlan;
use App\Models\AppraisalRecommendation;
use App\Models\AppraisalSession;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeAppraisal;
use App\Models\FeedbackCampaign;
use App\Models\Goal;
use App\Models\PerformanceCycle;
use App\Models\PerformanceReview;
use App\Models\PerformanceReviewCompetencyEvaluation;
use App\Models\TalentMatrixEntry;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppraisalService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
        protected FeedbackService $feedbackService,
    ) {}

    // -------------------------------------------------------------------------
    // Session lifecycle
    // -------------------------------------------------------------------------

    public function createSession(array $data, User $actor): AppraisalSession
    {
        return DB::transaction(function () use ($data, $actor): AppraisalSession {
            $cycle = $this->assertOrgCycle((int) $data['performance_cycle_id']);
            $status = $data['status'] ?? 'draft';
            $this->assertConfigKey('appraisal_session_statuses', $status);

            $ratingWeights = $this->normalizeRatingWeights($data['rating_weights'] ?? null);

            $session = AppraisalSession::query()->create([
                'performance_cycle_id' => $cycle->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => $status,
                'rating_weights' => $ratingWeights,
                'talent_matrix_config' => $data['talent_matrix_config'] ?? config('hrms.appraisal.default_talent_matrix'),
                'created_by' => $actor->id,
            ]);

            $this->auditLogger->log($session, 'appraisal_session_created', [
                'cycle_id' => $cycle->id,
                'rating_weights' => $ratingWeights,
            ], $actor);

            event(AppraisalSessionCreated::forModel($session, ['actor_id' => $actor->id]));

            return $session->fresh(['cycle']);
        });
    }

    public function updateSession(AppraisalSession $session, array $data, User $actor): AppraisalSession
    {
        return DB::transaction(function () use ($session, $data, $actor): AppraisalSession {
            $session->refresh();

            if (! $session->isEditable()) {
                throw ValidationException::withMessages([
                    'session' => 'Only draft or scheduled sessions can be updated.',
                ]);
            }

            if (isset($data['performance_cycle_id'])) {
                $this->assertOrgCycle((int) $data['performance_cycle_id']);
            }
            if (isset($data['status'])) {
                $this->assertConfigKey('appraisal_session_statuses', $data['status']);
            }

            $updates = collect($data)->only([
                'performance_cycle_id', 'name', 'description', 'start_date', 'end_date', 'status', 'talent_matrix_config',
            ])->filter(fn ($v) => $v !== null)->all();

            if (array_key_exists('rating_weights', $data)) {
                $updates['rating_weights'] = $this->normalizeRatingWeights($data['rating_weights']);
            }

            $session->update($updates);

            $this->auditLogger->log($session, 'appraisal_session_updated', [], $actor);

            return $session->fresh(['cycle']);
        });
    }

    public function activateSession(AppraisalSession $session, User $actor): AppraisalSession
    {
        return DB::transaction(function () use ($session, $actor): AppraisalSession {
            $session->refresh();

            if (! in_array($session->status, config('hrms.appraisal.activatable_session_statuses', ['draft', 'scheduled']), true)) {
                throw ValidationException::withMessages([
                    'session' => 'Only draft or scheduled sessions can be activated.',
                ]);
            }

            $session->update(['status' => 'active']);

            $this->auditLogger->log($session, 'appraisal_session_activated', [], $actor);

            return $session->fresh();
        });
    }

    public function closeSession(AppraisalSession $session, User $actor): AppraisalSession
    {
        return DB::transaction(function () use ($session, $actor): AppraisalSession {
            $session->refresh();

            if (! in_array($session->status, config('hrms.appraisal.closable_session_statuses', ['active']), true)) {
                throw ValidationException::withMessages([
                    'session' => 'Only active sessions can be closed.',
                ]);
            }

            $session->update(['status' => 'closed']);

            $this->auditLogger->log($session, 'appraisal_session_closed', [], $actor);

            return $session->fresh();
        });
    }

    public function archiveSession(AppraisalSession $session, User $actor): AppraisalSession
    {
        return DB::transaction(function () use ($session, $actor): AppraisalSession {
            $session->refresh();

            if ($session->status !== 'closed') {
                throw ValidationException::withMessages([
                    'session' => 'Only closed sessions can be archived.',
                ]);
            }

            $session->update(['status' => 'archived']);

            $this->auditLogger->log($session, 'appraisal_session_archived', [], $actor);

            return $session->fresh();
        });
    }

    // -------------------------------------------------------------------------
    // Appraisal generation & rating
    // -------------------------------------------------------------------------

    /**
     * @param  array<int>|null  $employeeIds
     * @return Collection<int, EmployeeAppraisal>
     */
    public function generateAppraisals(AppraisalSession $session, ?array $employeeIds, User $actor): Collection
    {
        return DB::transaction(function () use ($session, $employeeIds, $actor): Collection {
            $session->refresh();

            if (! in_array($session->status, ['active', 'scheduled'], true)) {
                throw ValidationException::withMessages([
                    'session' => 'Appraisals can only be generated for active or scheduled sessions.',
                ]);
            }

            $employeesQuery = Employee::query()
                ->where('status', 'active');

            if ($employeeIds !== null && $employeeIds !== []) {
                $employeesQuery->whereIn('id', $employeeIds);
            }

            $employees = $employeesQuery->get();
            $generated = collect();

            foreach ($employees as $employee) {
                $existing = EmployeeAppraisal::query()
                    ->where('appraisal_session_id', $session->id)
                    ->where('employee_id', $employee->id)
                    ->first();

                if ($existing) {
                    continue;
                }

                $ratingResult = $this->calculateRatingForEmployee(
                    $employee->id,
                    $session->performance_cycle_id,
                    $session->rating_weights ?? config('hrms.appraisal.default_rating_weights')
                );

                $appraisal = EmployeeAppraisal::query()->create([
                    'appraisal_session_id' => $session->id,
                    'performance_cycle_id' => $session->performance_cycle_id,
                    'employee_id' => $employee->id,
                    'manager_employee_id' => $employee->reporting_manager_id,
                    'status' => 'generated',
                    'manager_rating' => $ratingResult['score'],
                    'final_rating' => $ratingResult['score'],
                    'rating_breakdown' => $ratingResult['breakdown'],
                    'rating_calculation_snapshot' => $ratingResult['snapshot'],
                ]);

                AppraisalDevelopmentPlan::query()->create([
                    'employee_appraisal_id' => $appraisal->id,
                ]);

                $this->auditLogger->log($appraisal, 'employee_appraisal_generated', [
                    'employee_id' => $employee->id,
                    'manager_rating' => $ratingResult['score'],
                ], $actor);

                event(AppraisalGenerated::forModel($appraisal, [
                    'actor_id' => $actor->id,
                    'session_id' => $session->id,
                ]));

                $generated->push($appraisal->fresh(['employee', 'developmentPlan']));
            }

            return $generated;
        });
    }

    /**
     * @return array{score: float|null, breakdown: array<string, mixed>, snapshot: array<string, mixed>}
     */
    public function calculateRatingForEmployee(int $employeeId, int $cycleId, ?array $weights = null): array
    {
        $weights = $this->normalizeRatingWeights($weights);
        $maxRating = (float) config('hrms.appraisal.rating_scale_max', 5);

        $goalsScore = $this->calculateGoalsScore($employeeId, $cycleId, $maxRating);
        $competenciesScore = $this->calculateCompetenciesScore($employeeId, $cycleId);
        $managerScore = $this->calculateReviewScore($employeeId, $cycleId, 'manager');
        $selfScore = $this->calculateReviewScore($employeeId, $cycleId, 'self');
        $feedbackScore = $this->calculateFeedbackScore($employeeId, $cycleId);

        $components = [
            'goals' => ['score' => $goalsScore, 'weight' => $weights['goals'] ?? 0],
            'competencies' => ['score' => $competenciesScore, 'weight' => $weights['competencies'] ?? 0],
            'manager_review' => ['score' => $managerScore, 'weight' => $weights['manager_review'] ?? 0],
            'self_review' => ['score' => $selfScore, 'weight' => $weights['self_review'] ?? 0],
            'feedback_360' => ['score' => $feedbackScore, 'weight' => $weights['feedback_360'] ?? 0],
        ];

        $weightedSum = 0.0;
        $activeWeight = 0.0;

        foreach ($components as $key => $component) {
            if ($component['score'] === null || (float) $component['weight'] <= 0) {
                $components[$key]['weighted_contribution'] = null;

                continue;
            }

            $contribution = ((float) $component['score'] * (float) $component['weight']) / 100;
            $components[$key]['weighted_contribution'] = round($contribution, 4);
            $weightedSum += $contribution;
            $activeWeight += (float) $component['weight'];
        }

        $finalScore = $activeWeight > 0 ? round($weightedSum * (100 / $activeWeight), 2) : null;

        return [
            'score' => $finalScore,
            'breakdown' => $components,
            'snapshot' => [
                'weights' => $weights,
                'active_weight_total' => $activeWeight,
                'calculated_at' => Carbon::now()->toIso8601String(),
            ],
        ];
    }

    public function recalculateAppraisalRating(EmployeeAppraisal $appraisal, User $actor): EmployeeAppraisal
    {
        return DB::transaction(function () use ($appraisal, $actor): EmployeeAppraisal {
            $appraisal->refresh();

            if ($appraisal->isImmutable()) {
                throw ValidationException::withMessages([
                    'appraisal' => 'Closed appraisals cannot be recalculated.',
                ]);
            }

            $session = $this->assertOrgSession((int) $appraisal->appraisal_session_id);
            $result = $this->calculateRatingForEmployee(
                $appraisal->employee_id,
                $appraisal->performance_cycle_id,
                $session->rating_weights
            );

            $appraisal->update([
                'manager_rating' => $result['score'],
                'final_rating' => $appraisal->calibrated_rating ?? $result['score'],
                'rating_breakdown' => $result['breakdown'],
                'rating_calculation_snapshot' => $result['snapshot'],
            ]);

            $this->auditLogger->log($appraisal, 'employee_appraisal_rating_recalculated', [
                'manager_rating' => $result['score'],
            ], $actor);

            return $appraisal->fresh();
        });
    }

    // -------------------------------------------------------------------------
    // Manager & HR workflow
    // -------------------------------------------------------------------------

    public function updateAppraisal(EmployeeAppraisal $appraisal, array $data, User $actor): EmployeeAppraisal
    {
        return DB::transaction(function () use ($appraisal, $data, $actor): EmployeeAppraisal {
            $appraisal->refresh();

            if ($appraisal->isImmutable()) {
                throw ValidationException::withMessages([
                    'appraisal' => 'Closed appraisals are immutable.',
                ]);
            }

            $updates = collect($data)->only([
                'final_comments', 'overall_summary', 'manager_recommendation',
                'hr_recommendation', 'executive_notes',
            ])->filter(fn ($v) => $v !== null)->all();

            if ($updates !== []) {
                if (in_array($appraisal->status, ['generated'], true)) {
                    $updates['status'] = 'in_progress';
                }

                $appraisal->update($updates);
                $this->auditLogger->log($appraisal, 'employee_appraisal_updated', [], $actor);
            }

            return $appraisal->fresh(['employee', 'developmentPlan', 'recommendations']);
        });
    }

    public function submitAppraisal(EmployeeAppraisal $appraisal, array $data, User $actor): EmployeeAppraisal
    {
        return DB::transaction(function () use ($appraisal, $data, $actor): EmployeeAppraisal {
            $appraisal->refresh();

            if (! in_array($appraisal->status, config('hrms.appraisal.manager_submittable_statuses', ['generated', 'in_progress']), true)) {
                throw ValidationException::withMessages([
                    'appraisal' => 'This appraisal cannot be submitted in its current status.',
                ]);
            }

            $this->updateAppraisal($appraisal, $data, $actor);
            $appraisal->refresh();

            $appraisal->update([
                'status' => 'submitted',
                'submitted_at' => Carbon::now(),
            ]);

            $this->auditLogger->log($appraisal, 'employee_appraisal_submitted', [], $actor);

            event(AppraisalSubmitted::forModel($appraisal, ['actor_id' => $actor->id]));

            return $appraisal->fresh();
        });
    }

    public function hrReviewAppraisal(EmployeeAppraisal $appraisal, array $data, User $actor): EmployeeAppraisal
    {
        return DB::transaction(function () use ($appraisal, $data, $actor): EmployeeAppraisal {
            $appraisal->refresh();

            if (! in_array($appraisal->status, ['submitted', 'calibrated'], true)) {
                throw ValidationException::withMessages([
                    'appraisal' => 'Only submitted or calibrated appraisals can receive HR review.',
                ]);
            }

            $updates = collect($data)->only(['hr_recommendation', 'executive_notes'])->filter(fn ($v) => $v !== null)->all();
            $updates['status'] = 'hr_reviewed';
            $updates['hr_reviewed_at'] = Carbon::now();

            $appraisal->update($updates);

            $this->auditLogger->log($appraisal, 'employee_appraisal_hr_reviewed', [], $actor);

            return $appraisal->fresh();
        });
    }

    public function closeAppraisal(EmployeeAppraisal $appraisal, User $actor): EmployeeAppraisal
    {
        return DB::transaction(function () use ($appraisal, $actor): EmployeeAppraisal {
            $appraisal->refresh();

            if ($appraisal->isImmutable()) {
                throw ValidationException::withMessages([
                    'appraisal' => 'Appraisal is already closed.',
                ]);
            }

            if (! in_array($appraisal->status, ['submitted', 'hr_reviewed', 'calibrated'], true)) {
                throw ValidationException::withMessages([
                    'appraisal' => 'Appraisal must be submitted before closure.',
                ]);
            }

            $finalRating = $appraisal->calibrated_rating ?? $appraisal->manager_rating;

            $appraisal->update([
                'status' => 'closed',
                'final_rating' => $finalRating,
                'closed_at' => Carbon::now(),
            ]);

            $this->auditLogger->log($appraisal, 'employee_appraisal_closed', [
                'final_rating' => $finalRating,
                'manager_rating' => $appraisal->manager_rating,
                'calibrated_rating' => $appraisal->calibrated_rating,
            ], $actor);

            event(AppraisalClosed::forModel($appraisal, ['actor_id' => $actor->id]));

            return $appraisal->fresh();
        });
    }

    // -------------------------------------------------------------------------
    // Development plans
    // -------------------------------------------------------------------------

    public function updateDevelopmentPlan(EmployeeAppraisal $appraisal, array $data, User $actor): AppraisalDevelopmentPlan
    {
        return DB::transaction(function () use ($appraisal, $data, $actor): AppraisalDevelopmentPlan {
            $appraisal->refresh();

            if ($appraisal->isImmutable()) {
                throw ValidationException::withMessages([
                    'appraisal' => 'Development plans cannot be updated after appraisal closure.',
                ]);
            }

            $plan = $appraisal->developmentPlan ?? AppraisalDevelopmentPlan::query()->create([
                'employee_appraisal_id' => $appraisal->id,
            ]);

            $plan->update(collect($data)->only([
                'strengths', 'improvement_areas', 'learning_objectives',
                'required_training', 'career_aspirations', 'target_completion_date',
            ])->filter(fn ($v) => $v !== null)->all());

            $this->auditLogger->log($plan, 'appraisal_development_plan_updated', [
                'employee_appraisal_id' => $appraisal->id,
            ], $actor);

            return $plan->fresh();
        });
    }

    // -------------------------------------------------------------------------
    // Recommendations
    // -------------------------------------------------------------------------

    public function savePromotionRecommendation(EmployeeAppraisal $appraisal, array $data, User $actor): AppraisalRecommendation
    {
        return DB::transaction(function () use ($appraisal, $data, $actor): AppraisalRecommendation {
            $appraisal->refresh();

            if ($appraisal->isImmutable()) {
                throw ValidationException::withMessages([
                    'appraisal' => 'Recommendations cannot be changed after appraisal closure.',
                ]);
            }

            if (isset($data['promotion_recommendation'])) {
                $this->assertConfigKey('promotion_recommendation_levels', $data['promotion_recommendation']);
            }

            if (isset($data['target_designation_id'])) {
                $this->assertOrgDesignation((int) $data['target_designation_id']);
            }

            $recommendation = AppraisalRecommendation::query()->updateOrCreate(
                [
                    'employee_appraisal_id' => $appraisal->id,
                    'recommendation_type' => 'promotion',
                ],
                [
                    'promotion_recommendation' => $data['promotion_recommendation'] ?? null,
                    'target_designation_id' => $data['target_designation_id'] ?? null,
                    'effective_date' => $data['effective_date'] ?? null,
                    'justification' => $data['justification'] ?? null,
                ]
            );

            $this->auditLogger->log($recommendation, 'promotion_recommendation_saved', [
                'employee_appraisal_id' => $appraisal->id,
                'promotion_recommendation' => $recommendation->promotion_recommendation,
            ], $actor);

            event(PromotionRecommended::forModel($recommendation, [
                'actor_id' => $actor->id,
                'employee_appraisal_id' => $appraisal->id,
            ]));

            return $recommendation->fresh(['targetDesignation']);
        });
    }

    public function saveCompensationRecommendation(EmployeeAppraisal $appraisal, array $data, User $actor): AppraisalRecommendation
    {
        return DB::transaction(function () use ($appraisal, $data, $actor): AppraisalRecommendation {
            $appraisal->refresh();

            if ($appraisal->isImmutable()) {
                throw ValidationException::withMessages([
                    'appraisal' => 'Recommendations cannot be changed after appraisal closure.',
                ]);
            }

            $recommendation = AppraisalRecommendation::query()->updateOrCreate(
                [
                    'employee_appraisal_id' => $appraisal->id,
                    'recommendation_type' => 'compensation',
                ],
                [
                    'increment_percent' => $data['increment_percent'] ?? null,
                    'bonus_recommendation' => $data['bonus_recommendation'] ?? null,
                    'equity_recommendation' => $data['equity_recommendation'] ?? null,
                    'adjustment_notes' => $data['adjustment_notes'] ?? null,
                ]
            );

            $this->auditLogger->log($recommendation, 'compensation_recommendation_saved', [
                'employee_appraisal_id' => $appraisal->id,
            ], $actor);

            event(CompensationRecommended::forModel($recommendation, [
                'actor_id' => $actor->id,
                'employee_appraisal_id' => $appraisal->id,
            ]));

            return $recommendation->fresh();
        });
    }

    public function saveSuccessionRecommendation(EmployeeAppraisal $appraisal, array $data, User $actor): AppraisalRecommendation
    {
        return DB::transaction(function () use ($appraisal, $data, $actor): AppraisalRecommendation {
            $appraisal->refresh();

            if ($appraisal->isImmutable()) {
                throw ValidationException::withMessages([
                    'appraisal' => 'Recommendations cannot be changed after appraisal closure.',
                ]);
            }

            if (isset($data['readiness_level'])) {
                $this->assertConfigKey('succession_readiness_levels', $data['readiness_level']);
            }

            $recommendation = AppraisalRecommendation::query()->updateOrCreate(
                [
                    'employee_appraisal_id' => $appraisal->id,
                    'recommendation_type' => 'succession',
                ],
                [
                    'critical_role_flag' => (bool) ($data['critical_role_flag'] ?? false),
                    'readiness_level' => $data['readiness_level'] ?? null,
                    'succession_notes' => $data['succession_notes'] ?? null,
                ]
            );

            $this->auditLogger->log($recommendation, 'succession_recommendation_saved', [
                'employee_appraisal_id' => $appraisal->id,
            ], $actor);

            return $recommendation->fresh();
        });
    }

    // -------------------------------------------------------------------------
    // Calibration
    // -------------------------------------------------------------------------

    public function createCalibration(AppraisalSession $session, array $data, User $actor): AppraisalCalibration
    {
        return DB::transaction(function () use ($session, $data, $actor): AppraisalCalibration {
            $this->assertOrgSession($session->id);
            $status = $data['status'] ?? 'draft';
            $this->assertConfigKey('appraisal_calibration_statuses', $status);

            $calibration = AppraisalCalibration::query()->create([
                'appraisal_session_id' => $session->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => $status,
                'participant_employee_ids' => $data['participant_employee_ids'] ?? [],
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'created_by' => $actor->id,
            ]);

            $this->auditLogger->log($calibration, 'appraisal_calibration_created', [
                'session_id' => $session->id,
            ], $actor);

            return $calibration->fresh(['session']);
        });
    }

    public function applyCalibrationAdjustments(AppraisalCalibration $calibration, array $adjustments, User $actor): AppraisalCalibration
    {
        return DB::transaction(function () use ($calibration, $adjustments, $actor): AppraisalCalibration {
            $calibration->refresh();

            if ($calibration->isCompleted()) {
                throw ValidationException::withMessages([
                    'calibration' => 'Completed calibration sessions cannot be modified.',
                ]);
            }

            $processed = [];
            $session = $this->assertOrgSession((int) $calibration->appraisal_session_id);

            foreach ($adjustments as $adjustment) {
                $appraisal = EmployeeAppraisal::query()
                    ->where('appraisal_session_id', $session->id)
                    ->where('id', (int) $adjustment['employee_appraisal_id'])
                    ->firstOrFail();

                $originalRating = $appraisal->manager_rating;
                $proposedRating = isset($adjustment['proposed_rating']) ? (float) $adjustment['proposed_rating'] : null;
                $finalRating = isset($adjustment['final_rating']) ? (float) $adjustment['final_rating'] : $proposedRating;

                $processed[] = [
                    'employee_appraisal_id' => $appraisal->id,
                    'employee_id' => $appraisal->employee_id,
                    'original_rating' => $originalRating,
                    'proposed_rating' => $proposedRating,
                    'final_rating' => $finalRating,
                    'comments' => $adjustment['comments'] ?? null,
                    'adjusted_at' => Carbon::now()->toIso8601String(),
                    'adjusted_by' => $actor->id,
                ];

                if ($finalRating !== null) {
                    $appraisal->update([
                        'calibrated_rating' => $finalRating,
                        'final_rating' => $finalRating,
                        'appraisal_calibration_id' => $calibration->id,
                        'calibration_comments' => $adjustment['comments'] ?? null,
                        'status' => 'calibrated',
                    ]);

                    $this->auditLogger->log($appraisal, 'employee_appraisal_calibrated', [
                        'calibration_id' => $calibration->id,
                        'original_rating' => $originalRating,
                        'calibrated_rating' => $finalRating,
                    ], $actor);
                }
            }

            $existing = $calibration->adjustments ?? [];
            $calibration->update([
                'adjustments' => array_merge($existing, $processed),
                'status' => 'in_progress',
                'started_at' => $calibration->started_at ?? Carbon::now(),
            ]);

            $this->auditLogger->log($calibration, 'appraisal_calibration_adjustments_applied', [
                'adjustment_count' => count($processed),
            ], $actor);

            return $calibration->fresh();
        });
    }

    public function approveCalibration(AppraisalCalibration $calibration, array $data, User $actor): AppraisalCalibration
    {
        return DB::transaction(function () use ($calibration, $data, $actor): AppraisalCalibration {
            $calibration->refresh();

            if ($calibration->isCompleted()) {
                throw ValidationException::withMessages([
                    'calibration' => 'Calibration is already completed.',
                ]);
            }

            $calibration->update([
                'status' => 'completed',
                'session_comments' => $data['session_comments'] ?? $calibration->session_comments,
                'completed_at' => Carbon::now(),
                'approved_by' => $actor->id,
                'approved_at' => Carbon::now(),
            ]);

            $this->auditLogger->log($calibration, 'appraisal_calibration_approved', [], $actor);

            event(AppraisalCalibrated::forModel($calibration, ['actor_id' => $actor->id]));

            return $calibration->fresh();
        });
    }

    // -------------------------------------------------------------------------
    // Talent matrix
    // -------------------------------------------------------------------------

    public function classifyTalent(EmployeeAppraisal $appraisal, array $data, User $actor): TalentMatrixEntry
    {
        return DB::transaction(function () use ($appraisal, $data, $actor): TalentMatrixEntry {
            $appraisal->refresh();
            $session = $this->assertOrgSession((int) $appraisal->appraisal_session_id);

            $matrixConfig = $session->talent_matrix_config ?? config('hrms.appraisal.default_talent_matrix');
            $performanceBand = (int) ($data['performance_band'] ?? 2);
            $potentialBand = (int) ($data['potential_band'] ?? 2);

            $classification = $data['classification']
                ?? ($matrixConfig['classifications']["{$performanceBand}-{$potentialBand}"] ?? null);

            $entry = TalentMatrixEntry::query()->updateOrCreate(
                [
                    'appraisal_session_id' => $session->id,
                    'employee_id' => $appraisal->employee_id,
                ],
                [
                    'employee_appraisal_id' => $appraisal->id,
                    'performance_band' => $performanceBand,
                    'potential_band' => $potentialBand,
                    'performance_score' => $data['performance_score'] ?? $appraisal->final_rating,
                    'potential_score' => $data['potential_score'] ?? null,
                    'classification' => $classification,
                    'matrix_config_snapshot' => $matrixConfig,
                    'notes' => $data['notes'] ?? null,
                ]
            );

            $this->auditLogger->log($entry, 'talent_matrix_entry_classified', [
                'classification' => $classification,
                'performance_band' => $performanceBand,
                'potential_band' => $potentialBand,
            ], $actor);

            return $entry->fresh(['employee', 'appraisal']);
        });
    }

    /**
     * @return array<string, Collection<int, TalentMatrixEntry>>
     */
    public function buildTalentMatrix(AppraisalSession $session): array
    {
        $session->refresh();
        $config = $session->talent_matrix_config ?? config('hrms.appraisal.default_talent_matrix');
        $gridSize = (int) ($config['grid_size'] ?? 3);

        $entries = TalentMatrixEntry::query()
            ->where('appraisal_session_id', $session->id)
            ->with(['employee', 'appraisal'])
            ->get();

        $matrix = [];
        for ($p = 1; $p <= $gridSize; $p++) {
            for ($perf = 1; $perf <= $gridSize; $perf++) {
                $key = "{$perf}-{$p}";
                $matrix[$key] = $entries->filter(
                    fn (TalentMatrixEntry $e) => $e->performance_band === $perf && $e->potential_band === $p
                )->values();
            }
        }

        return [
            'config' => $config,
            'cells' => $matrix,
            'entries' => $entries,
        ];
    }

    // -------------------------------------------------------------------------
    // Rating component calculators
    // -------------------------------------------------------------------------

    protected function calculateGoalsScore(int $employeeId, int $cycleId, float $maxRating): ?float
    {
        $goals = Goal::query()
            ->where('employee_id', $employeeId)
            ->where('performance_cycle_id', $cycleId)
            ->where('assignee_type', 'employee')
            ->whereIn('status', config('hrms.goal_weighting.statuses_included_in_total', ['assigned', 'in_progress', 'completed']))
            ->get();

        if ($goals->isEmpty()) {
            return null;
        }

        $totalWeight = $goals->sum(fn (Goal $g) => (float) $g->weight);
        if ($totalWeight <= 0) {
            return round($goals->avg(fn (Goal $g) => (float) $g->achievement_percentage) / (100 / $maxRating), 2);
        }

        $weightedAchievement = $goals->sum(
            fn (Goal $g) => ((float) $g->achievement_percentage * (float) $g->weight) / $totalWeight
        );

        return round($weightedAchievement / (100 / $maxRating), 2);
    }

    protected function calculateCompetenciesScore(int $employeeId, int $cycleId): ?float
    {
        $review = $this->findClosedManagerReview($employeeId, $cycleId);

        if (! $review) {
            return null;
        }

        $ratings = PerformanceReviewCompetencyEvaluation::query()
            ->where('performance_review_id', $review->id)
            ->whereNotNull('rating')
            ->pluck('rating');

        if ($ratings->isEmpty()) {
            return null;
        }

        return round((float) $ratings->avg(), 2);
    }

    protected function calculateReviewScore(int $employeeId, int $cycleId, string $reviewType): ?float
    {
        $review = PerformanceReview::query()
            ->where('employee_id', $employeeId)
            ->where('performance_cycle_id', $cycleId)
            ->where('review_type', $reviewType)
            ->whereIn('status', ['submitted', 'reviewed', 'closed'])
            ->latest('id')
            ->first();

        if (! $review) {
            return null;
        }

        $ratings = PerformanceReviewCompetencyEvaluation::query()
            ->where('performance_review_id', $review->id)
            ->whereNotNull('rating')
            ->pluck('rating');

        if ($ratings->isEmpty()) {
            return null;
        }

        return round((float) $ratings->avg(), 2);
    }

    protected function calculateFeedbackScore(int $employeeId, int $cycleId): ?float
    {
        $campaign = FeedbackCampaign::query()
            ->where('performance_cycle_id', $cycleId)
            ->whereIn('status', ['active', 'closed'])
            ->latest('id')
            ->first();

        if (! $campaign) {
            return null;
        }

        $aggregation = $this->feedbackService->aggregateFeedback($campaign, $employeeId);

        return $aggregation['overall_average'] !== null
            ? (float) $aggregation['overall_average']
            : null;
    }

    protected function findClosedManagerReview(int $employeeId, int $cycleId): ?PerformanceReview
    {
        return PerformanceReview::query()
            ->where('employee_id', $employeeId)
            ->where('performance_cycle_id', $cycleId)
            ->where('review_type', 'manager')
            ->whereIn('status', ['submitted', 'reviewed', 'closed'])
            ->latest('id')
            ->first();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, float|int|string>|null  $weights
     * @return array<string, float>
     */
    protected function normalizeRatingWeights(?array $weights): array
    {
        $defaults = config('hrms.appraisal.default_rating_weights', []);
        $merged = array_merge($defaults, $weights ?? []);
        $allowed = config('hrms.appraisal.rating_weight_keys', array_keys($defaults));

        $normalized = [];
        foreach ($allowed as $key) {
            if (isset($merged[$key])) {
                $normalized[$key] = (float) $merged[$key];
            }
        }

        $total = array_sum($normalized);
        $tolerance = (float) config('hrms.appraisal.rating_weight_tolerance', 0.01);

        if ($total > 0 && abs($total - 100) > $tolerance) {
            throw ValidationException::withMessages([
                'rating_weights' => "Rating weights must total 100. Current total: {$total}.",
            ]);
        }

        return $normalized;
    }

    protected function assertOrgCycle(int $cycleId): PerformanceCycle
    {
        return PerformanceCycle::query()->findOrFail($cycleId);
    }

    protected function assertOrgSession(int $sessionId): AppraisalSession
    {
        return AppraisalSession::query()->findOrFail($sessionId);
    }

    protected function assertOrgEmployee(int $employeeId): Employee
    {
        return Employee::query()->findOrFail($employeeId);
    }

    protected function assertOrgDesignation(int $designationId): Designation
    {
        return Designation::query()->findOrFail($designationId);
    }

    protected function assertConfigKey(string $catalog, string $key): void
    {
        if (! array_key_exists($key, config("hrms.{$catalog}", []))) {
            throw ValidationException::withMessages([
                $catalog => "Invalid value: {$key}.",
            ]);
        }
    }
}
