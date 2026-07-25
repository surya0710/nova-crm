<?php

namespace App\Services\Hrms;

use App\Events\GoalAssigned;
use App\Events\GoalCancelled;
use App\Events\GoalCompleted;
use App\Events\GoalCreated;
use App\Events\GoalProgressUpdated;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Goal;
use App\Models\GoalCategory;
use App\Models\GoalCheckin;
use App\Models\GoalProgressUpdate;
use App\Models\GoalTemplate;
use App\Models\HrmsTeam;
use App\Models\Kpi;
use App\Models\PerformanceCycle;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoalManagementService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
    ) {}

    // -------------------------------------------------------------------------
    // Goal Categories
    // -------------------------------------------------------------------------

    public function createCategory(array $data, User $actor): GoalCategory
    {
        return DB::transaction(function () use ($data, $actor): GoalCategory {
            $category = GoalCategory::query()->create([
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $this->auditLogger->log($category, 'goal_category_created', [
                'name' => $category->name,
                'code' => $category->code,
            ], $actor);

            return $category;
        });
    }

    public function updateCategory(GoalCategory $category, array $data, User $actor): GoalCategory
    {
        return DB::transaction(function () use ($category, $data, $actor): GoalCategory {
            $before = $category->only(['name', 'code', 'description', 'is_active']);
            $category->update([
                'name' => $data['name'] ?? $category->name,
                'code' => $data['code'] ?? $category->code,
                'description' => array_key_exists('description', $data) ? $data['description'] : $category->description,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $category->is_active,
            ]);

            $this->auditLogger->log($category, 'goal_category_updated', [
                'before' => $before,
                'after' => $category->only(array_keys($before)),
            ], $actor);

            return $category->fresh();
        });
    }

    public function deleteCategory(GoalCategory $category, User $actor): void
    {
        DB::transaction(function () use ($category, $actor): void {
            if ($category->templates()->exists()) {
                throw ValidationException::withMessages([
                    'category' => 'Cannot delete a goal category that still has templates.',
                ]);
            }

            $this->auditLogger->log($category, 'goal_category_deleted', [
                'name' => $category->name,
                'code' => $category->code,
            ], $actor);

            $category->delete();
        });
    }

    // -------------------------------------------------------------------------
    // Goal Library (Templates)
    // -------------------------------------------------------------------------

    public function createTemplate(array $data, User $actor): GoalTemplate
    {
        return DB::transaction(function () use ($data, $actor): GoalTemplate {
            if (! empty($data['goal_category_id'])) {
                $this->assertOrgCategory((int) $data['goal_category_id']);
            }

            $this->assertConfigKey('goal_types', $data['goal_type'] ?? 'individual');
            $this->assertConfigKey('goal_measurement_types', $data['measurement_type'] ?? 'percentage');

            $template = GoalTemplate::query()->create([
                'goal_category_id' => $data['goal_category_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'goal_type' => $data['goal_type'] ?? 'individual',
                'default_weight' => $data['default_weight'] ?? 0,
                'measurement_type' => $data['measurement_type'] ?? 'percentage',
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $this->auditLogger->log($template, 'goal_template_created', [
                'title' => $template->title,
                'goal_type' => $template->goal_type,
            ], $actor);

            return $template;
        });
    }

    public function updateTemplate(GoalTemplate $template, array $data, User $actor): GoalTemplate
    {
        return DB::transaction(function () use ($template, $data, $actor): GoalTemplate {
            if (array_key_exists('goal_category_id', $data) && $data['goal_category_id']) {
                $this->assertOrgCategory((int) $data['goal_category_id']);
            }
            if (! empty($data['goal_type'])) {
                $this->assertConfigKey('goal_types', $data['goal_type']);
            }
            if (! empty($data['measurement_type'])) {
                $this->assertConfigKey('goal_measurement_types', $data['measurement_type']);
            }

            $before = $template->only([
                'goal_category_id', 'title', 'description', 'goal_type',
                'default_weight', 'measurement_type', 'is_active',
            ]);

            $template->update([
                'goal_category_id' => array_key_exists('goal_category_id', $data) ? $data['goal_category_id'] : $template->goal_category_id,
                'title' => $data['title'] ?? $template->title,
                'description' => array_key_exists('description', $data) ? $data['description'] : $template->description,
                'goal_type' => $data['goal_type'] ?? $template->goal_type,
                'default_weight' => array_key_exists('default_weight', $data) ? $data['default_weight'] : $template->default_weight,
                'measurement_type' => $data['measurement_type'] ?? $template->measurement_type,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $template->is_active,
            ]);

            $this->auditLogger->log($template, 'goal_template_updated', [
                'before' => $before,
                'after' => $template->only(array_keys($before)),
            ], $actor);

            return $template->fresh();
        });
    }

    public function deleteTemplate(GoalTemplate $template, User $actor): void
    {
        DB::transaction(function () use ($template, $actor): void {
            if ($template->goals()->exists()) {
                throw ValidationException::withMessages([
                    'template' => 'Cannot delete a goal template that is assigned to goals.',
                ]);
            }

            $this->auditLogger->log($template, 'goal_template_deleted', [
                'title' => $template->title,
            ], $actor);

            $template->delete();
        });
    }

    // -------------------------------------------------------------------------
    // KPI Library
    // -------------------------------------------------------------------------

    public function createKpi(array $data, User $actor): Kpi
    {
        return DB::transaction(function () use ($data, $actor): Kpi {
            $this->assertConfigKey('goal_measurement_types', $data['measurement_type'] ?? 'numeric');

            $kpi = Kpi::query()->create([
                'name' => $data['name'],
                'code' => $data['code'],
                'unit' => $data['unit'] ?? null,
                'measurement_type' => $data['measurement_type'] ?? 'numeric',
                'default_target' => $data['default_target'] ?? null,
                'description' => $data['description'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $this->auditLogger->log($kpi, 'kpi_created', [
                'name' => $kpi->name,
                'code' => $kpi->code,
            ], $actor);

            return $kpi;
        });
    }

    public function updateKpi(Kpi $kpi, array $data, User $actor): Kpi
    {
        return DB::transaction(function () use ($kpi, $data, $actor): Kpi {
            if (! empty($data['measurement_type'])) {
                $this->assertConfigKey('goal_measurement_types', $data['measurement_type']);
            }

            $before = $kpi->only([
                'name', 'code', 'unit', 'measurement_type', 'default_target', 'description', 'is_active',
            ]);

            $kpi->update([
                'name' => $data['name'] ?? $kpi->name,
                'code' => $data['code'] ?? $kpi->code,
                'unit' => array_key_exists('unit', $data) ? $data['unit'] : $kpi->unit,
                'measurement_type' => $data['measurement_type'] ?? $kpi->measurement_type,
                'default_target' => array_key_exists('default_target', $data) ? $data['default_target'] : $kpi->default_target,
                'description' => array_key_exists('description', $data) ? $data['description'] : $kpi->description,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $kpi->is_active,
            ]);

            $this->auditLogger->log($kpi, 'kpi_updated', [
                'before' => $before,
                'after' => $kpi->only(array_keys($before)),
            ], $actor);

            return $kpi->fresh();
        });
    }

    public function deleteKpi(Kpi $kpi, User $actor): void
    {
        DB::transaction(function () use ($kpi, $actor): void {
            if ($kpi->goals()->exists()) {
                throw ValidationException::withMessages([
                    'kpi' => 'Cannot delete a KPI that is linked to goals.',
                ]);
            }

            $this->auditLogger->log($kpi, 'kpi_deleted', [
                'name' => $kpi->name,
                'code' => $kpi->code,
            ], $actor);

            $kpi->delete();
        });
    }

    // -------------------------------------------------------------------------
    // Goal Assignment
    // -------------------------------------------------------------------------

    public function assignGoal(array $data, User $actor): Goal
    {
        return DB::transaction(function () use ($data, $actor): Goal {
            $cycle = $this->assertOrgCycle((int) $data['performance_cycle_id']);
            $template = null;
            $kpi = null;

            if (! empty($data['goal_template_id'])) {
                $template = $this->assertOrgTemplate((int) $data['goal_template_id']);
            }
            if (! empty($data['kpi_id'])) {
                $kpi = $this->assertOrgKpi((int) $data['kpi_id']);
            }
            if (! empty($data['goal_category_id'])) {
                $this->assertOrgCategory((int) $data['goal_category_id']);
            }

            $assigneeType = $data['assignee_type'] ?? 'employee';
            $this->assertConfigKey('goal_assignee_types', $assigneeType);
            $this->assertAssignee($assigneeType, $data);

            $goalType = $data['goal_type']
                ?? $template?->goal_type
                ?? match ($assigneeType) {
                    'team' => 'team',
                    'department' => 'department',
                    'organization' => 'organization',
                    default => 'individual',
                };
            $this->assertConfigKey('goal_types', $goalType);

            $measurementType = $data['measurement_type']
                ?? $template?->measurement_type
                ?? $kpi?->measurement_type
                ?? 'percentage';
            $this->assertConfigKey('goal_measurement_types', $measurementType);

            $weight = (float) ($data['weight']
                ?? $template?->default_weight
                ?? 0);

            $target = $data['target_value']
                ?? $kpi?->default_target
                ?? null;

            $status = $data['status'] ?? 'assigned';
            $this->assertConfigKey('goal_statuses', $status);

            $title = $data['title'] ?? $template?->title;
            if (! $title) {
                throw ValidationException::withMessages([
                    'title' => 'A goal title is required when no template is selected.',
                ]);
            }

            $goal = Goal::query()->create([
                'performance_cycle_id' => $cycle->id,
                'goal_template_id' => $template?->id,
                'kpi_id' => $kpi?->id,
                'goal_category_id' => $data['goal_category_id'] ?? $template?->goal_category_id,
                'title' => $title,
                'description' => $data['description'] ?? $template?->description,
                'goal_type' => $goalType,
                'assignee_type' => $assigneeType,
                'employee_id' => $assigneeType === 'employee' ? ($data['employee_id'] ?? null) : null,
                'team_id' => $assigneeType === 'team' ? ($data['team_id'] ?? null) : null,
                'department_id' => $assigneeType === 'department' ? ($data['department_id'] ?? null) : null,
                'measurement_type' => $measurementType,
                'target_value' => $target,
                'current_value' => $data['current_value'] ?? 0,
                'weight' => $weight,
                'achievement_percentage' => 0,
                'due_date' => $data['due_date'] ?? null,
                'status' => $status,
                'assigned_by' => $actor->id,
                'assigned_at' => in_array($status, ['assigned', 'in_progress'], true) ? Carbon::now() : null,
            ]);

            if ($assigneeType === 'employee' && $goal->employee_id) {
                $this->assertEmployeeWeightTotal($cycle->id, (int) $goal->employee_id);
            }

            $achievement = $this->calculateAchievement(
                $measurementType,
                (float) $goal->current_value,
                $goal->target_value !== null ? (float) $goal->target_value : null,
            );
            if ($achievement != (float) $goal->achievement_percentage) {
                $goal->update(['achievement_percentage' => $achievement]);
            }

            $this->auditLogger->log($goal, 'goal_created', [
                'title' => $goal->title,
                'assignee_type' => $goal->assignee_type,
                'status' => $goal->status,
                'weight' => (float) $goal->weight,
            ], $actor);

            event(GoalCreated::forModel($goal, ['actor_id' => $actor->id]));

            if (in_array($goal->status, ['assigned', 'in_progress'], true)) {
                $this->auditLogger->log($goal, 'goal_assigned', [
                    'title' => $goal->title,
                    'assignee_type' => $goal->assignee_type,
                    'employee_id' => $goal->employee_id,
                    'team_id' => $goal->team_id,
                    'department_id' => $goal->department_id,
                ], $actor);

                event(GoalAssigned::forModel($goal, ['actor_id' => $actor->id]));
            }

            return $goal->fresh();
        });
    }

    public function updateGoal(Goal $goal, array $data, User $actor): Goal
    {
        return DB::transaction(function () use ($goal, $data, $actor): Goal {
            if (! $goal->isEditable()) {
                throw ValidationException::withMessages([
                    'goal' => 'Completed or cancelled goals cannot be updated.',
                ]);
            }

            $before = $goal->only([
                'title', 'description', 'weight', 'target_value', 'due_date', 'status',
            ]);

            $weight = array_key_exists('weight', $data) ? (float) $data['weight'] : (float) $goal->weight;

            $goal->update([
                'title' => $data['title'] ?? $goal->title,
                'description' => array_key_exists('description', $data) ? $data['description'] : $goal->description,
                'weight' => $weight,
                'target_value' => array_key_exists('target_value', $data) ? $data['target_value'] : $goal->target_value,
                'due_date' => array_key_exists('due_date', $data) ? $data['due_date'] : $goal->due_date,
            ]);

            if ($goal->assignee_type === 'employee' && $goal->employee_id) {
                $this->assertEmployeeWeightTotal((int) $goal->performance_cycle_id, (int) $goal->employee_id);
            }

            $this->auditLogger->log($goal, 'goal_updated', [
                'before' => $before,
                'after' => $goal->only(array_keys($before)),
            ], $actor);

            return $goal->fresh();
        });
    }

    public function completeGoal(Goal $goal, User $actor): Goal
    {
        return DB::transaction(function () use ($goal, $actor): Goal {
            if ($goal->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'goal' => 'Cancelled goals cannot be completed.',
                ]);
            }
            if ($goal->status === 'completed') {
                return $goal;
            }

            $goal->update([
                'status' => 'completed',
                'completed_at' => Carbon::now(),
            ]);

            $this->auditLogger->log($goal, 'goal_completed', [
                'title' => $goal->title,
                'achievement_percentage' => (float) $goal->achievement_percentage,
            ], $actor);

            event(GoalCompleted::forModel($goal, ['actor_id' => $actor->id]));

            return $goal->fresh();
        });
    }

    public function cancelGoal(Goal $goal, User $actor): Goal
    {
        return DB::transaction(function () use ($goal, $actor): Goal {
            if ($goal->status === 'completed') {
                throw ValidationException::withMessages([
                    'goal' => 'Completed goals cannot be cancelled.',
                ]);
            }
            if ($goal->status === 'cancelled') {
                return $goal;
            }

            $goal->update([
                'status' => 'cancelled',
                'cancelled_at' => Carbon::now(),
            ]);

            $this->auditLogger->log($goal, 'goal_cancelled', [
                'title' => $goal->title,
            ], $actor);

            event(GoalCancelled::forModel($goal, ['actor_id' => $actor->id]));

            return $goal->fresh();
        });
    }

    // -------------------------------------------------------------------------
    // Progress & Achievement
    // -------------------------------------------------------------------------

    public function updateProgress(Goal $goal, array $data, User $actor): GoalProgressUpdate
    {
        return DB::transaction(function () use ($goal, $data, $actor): GoalProgressUpdate {
            if (! $goal->isEditable()) {
                throw ValidationException::withMessages([
                    'goal' => 'Progress cannot be updated on completed or cancelled goals.',
                ]);
            }

            $progressValue = (float) $data['progress_value'];
            $achievement = $this->calculateAchievement(
                $goal->measurement_type,
                $progressValue,
                $goal->target_value !== null ? (float) $goal->target_value : null,
            );

            $update = GoalProgressUpdate::query()->create([
                'goal_id' => $goal->id,
                'progress_value' => $progressValue,
                'achievement_percentage' => $achievement,
                'notes' => $data['notes'] ?? null,
                'updated_by' => $actor->id,
            ]);

            $status = $goal->status === 'assigned' ? 'in_progress' : $goal->status;

            $goal->update([
                'current_value' => $progressValue,
                'achievement_percentage' => $achievement,
                'status' => $status,
            ]);

            $this->auditLogger->log($goal, 'goal_progress_updated', [
                'progress_value' => $progressValue,
                'achievement_percentage' => $achievement,
                'update_id' => $update->id,
            ], $actor);

            event(GoalProgressUpdated::forModel($goal->fresh(), [
                'actor_id' => $actor->id,
                'progress_update_id' => $update->id,
            ]));

            return $update->fresh(['updater']);
        });
    }

    public function calculateAchievement(string $measurementType, float $current, ?float $target): float
    {
        return match ($measurementType) {
            'boolean' => $current >= 1 ? 100.0 : 0.0,
            'percentage' => round(min(max($current, 0), 100), 2),
            'numeric', 'currency', 'milestone' => $this->ratioAchievement($current, $target),
            default => $this->ratioAchievement($current, $target),
        };
    }

    // -------------------------------------------------------------------------
    // Check-ins
    // -------------------------------------------------------------------------

    public function recordCheckin(Goal $goal, array $data, User $actor): GoalCheckin
    {
        return DB::transaction(function () use ($goal, $data, $actor): GoalCheckin {
            if ($goal->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'goal' => 'Check-ins cannot be recorded on cancelled goals.',
                ]);
            }

            $checkin = GoalCheckin::query()->create([
                'goal_id' => $goal->id,
                'summary' => $data['summary'],
                'progress' => $data['progress'] ?? null,
                'risks' => $data['risks'] ?? null,
                'next_steps' => $data['next_steps'] ?? null,
                'checked_in_by' => $actor->id,
            ]);

            $this->auditLogger->log($goal, 'goal_checkin_recorded', [
                'checkin_id' => $checkin->id,
                'summary' => $checkin->summary,
            ], $actor);

            return $checkin->fresh(['author']);
        });
    }

    // -------------------------------------------------------------------------
    // Resolution Helpers
    // -------------------------------------------------------------------------

    /** @return Collection<int, Goal> */
    public function resolveActiveGoalsForEmployee(int $employeeId, ?int $cycleId = null): Collection
    {
        $query = Goal::query()
            ->where('employee_id', $employeeId)
            ->where('assignee_type', 'employee')
            ->whereIn('status', ['assigned', 'in_progress']);

        if ($cycleId) {
            $query->where('performance_cycle_id', $cycleId);
        }

        return $query->orderByDesc('id')->get();
    }

    public function employeeWeightTotal(int $cycleId, int $employeeId): float
    {
        $statuses = config('hrms.goal_weighting.statuses_included_in_total', [
            'draft', 'assigned', 'in_progress', 'completed',
        ]);

        return (float) Goal::query()
            ->where('performance_cycle_id', $cycleId)
            ->where('employee_id', $employeeId)
            ->where('assignee_type', 'employee')
            ->whereIn('status', $statuses)
            ->sum('weight');
    }

    public function assertEmployeeWeightsEqualRequired(int $cycleId, int $employeeId): void
    {
        $required = (float) config('hrms.goal_weighting.required_total', 100);
        $tolerance = (float) config('hrms.goal_weighting.tolerance', 0.01);
        $total = $this->employeeWeightTotal($cycleId, $employeeId);

        if (abs($total - $required) > $tolerance) {
            throw ValidationException::withMessages([
                'weight' => "Employee goal weights for this cycle must total {$required}%. Current total: {$total}%.",
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    protected function ratioAchievement(float $current, ?float $target): float
    {
        if ($target === null || abs($target) < 0.0000001) {
            return 0.0;
        }

        return round(min(max(($current / $target) * 100, 0), 999.99), 2);
    }

    protected function assertEmployeeWeightTotal(int $cycleId, int $employeeId): void
    {
        $required = (float) config('hrms.goal_weighting.required_total', 100);
        $tolerance = (float) config('hrms.goal_weighting.tolerance', 0.01);
        $total = $this->employeeWeightTotal($cycleId, $employeeId);

        if ($total > ($required + $tolerance)) {
            throw ValidationException::withMessages([
                'weight' => "Employee goal weights for this cycle must not exceed {$required}%. Current total: {$total}%.",
            ]);
        }
    }

    protected function assertAssignee(string $assigneeType, array $data): void
    {
        match ($assigneeType) {
            'employee' => $this->assertOrgEmployee((int) ($data['employee_id'] ?? 0)),
            'team' => $this->assertOrgTeam((int) ($data['team_id'] ?? 0)),
            'department' => $this->assertOrgDepartment((int) ($data['department_id'] ?? 0)),
            'organization' => null,
            default => throw ValidationException::withMessages([
                'assignee_type' => 'Invalid assignee type.',
            ]),
        };
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

    protected function assertOrgCategory(int $id): GoalCategory
    {
        $category = GoalCategory::query()->whereKey($id)->first();
        if (! $category) {
            throw ValidationException::withMessages(['goal_category_id' => 'Goal category not found in this organization.']);
        }

        return $category;
    }

    protected function assertOrgTemplate(int $id): GoalTemplate
    {
        $template = GoalTemplate::query()->whereKey($id)->where('is_active', true)->first();
        if (! $template) {
            throw ValidationException::withMessages(['goal_template_id' => 'Goal template not found in this organization.']);
        }

        return $template;
    }

    protected function assertOrgKpi(int $id): Kpi
    {
        $kpi = Kpi::query()->whereKey($id)->where('is_active', true)->first();
        if (! $kpi) {
            throw ValidationException::withMessages(['kpi_id' => 'KPI not found in this organization.']);
        }

        return $kpi;
    }

    protected function assertOrgCycle(int $id): PerformanceCycle
    {
        $cycle = PerformanceCycle::query()->whereKey($id)->first();
        if (! $cycle) {
            throw ValidationException::withMessages(['performance_cycle_id' => 'Performance cycle not found in this organization.']);
        }

        return $cycle;
    }

    protected function assertOrgEmployee(int $id): Employee
    {
        $employee = Employee::query()->whereKey($id)->first();
        if (! $employee) {
            throw ValidationException::withMessages(['employee_id' => 'Employee not found in this organization.']);
        }

        return $employee;
    }

    protected function assertOrgTeam(int $id): HrmsTeam
    {
        $team = HrmsTeam::query()->whereKey($id)->first();
        if (! $team) {
            throw ValidationException::withMessages(['team_id' => 'Team not found in this organization.']);
        }

        return $team;
    }

    protected function assertOrgDepartment(int $id): Department
    {
        $department = Department::query()->whereKey($id)->first();
        if (! $department) {
            throw ValidationException::withMessages(['department_id' => 'Department not found in this organization.']);
        }

        return $department;
    }
}
