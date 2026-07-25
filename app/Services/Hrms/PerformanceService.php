<?php

namespace App\Services\Hrms;

use App\Events\PerformanceConfigurationUpdated;
use App\Events\PerformanceCycleActivated;
use App\Events\PerformanceCycleCreated;
use App\Events\PerformanceTemplateCreated;
use App\Models\Competency;
use App\Models\CompetencyCategory;
use App\Models\PerformanceConfiguration;
use App\Models\PerformanceCycle;
use App\Models\PerformanceRatingScale;
use App\Models\PerformanceRatingScaleLevel;
use App\Models\PerformanceReviewTemplate;
use App\Models\PerformanceReviewTemplateCompetency;
use App\Models\PerformanceReviewTemplateSection;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PerformanceService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
    ) {}

    // -------------------------------------------------------------------------
    // Configuration
    // -------------------------------------------------------------------------

    public function getOrCreateConfiguration(): PerformanceConfiguration
    {
        $organization = $this->tenantContext->get();

        return PerformanceConfiguration::query()->firstOrCreate(
            ['organization_id' => $organization->id],
            [
                'default_review_frequency' => config('hrms.performance.default_review_frequency', 'annual'),
                'goal_weighting' => config('hrms.performance.default_goal_weighting', 50),
                'competency_weighting' => config('hrms.performance.default_competency_weighting', 50),
                'review_visibility' => config('hrms.performance.default_review_visibility', 'employee_and_manager'),
                'calibration_enabled' => config('hrms.performance.default_calibration_enabled', false),
            ],
        );
    }

    public function updateConfiguration(array $data, User $actor): PerformanceConfiguration
    {
        return DB::transaction(function () use ($data, $actor): PerformanceConfiguration {
            $configuration = $this->getOrCreateConfiguration();

            if (! empty($data['rating_scale_id'])) {
                $this->assertOrgRatingScale((int) $data['rating_scale_id']);
            }

            $goal = (float) ($data['goal_weighting'] ?? $configuration->goal_weighting);
            $competency = (float) ($data['competency_weighting'] ?? $configuration->competency_weighting);
            if (abs(($goal + $competency) - 100) > 0.0001) {
                throw ValidationException::withMessages([
                    'goal_weighting' => 'Goal and competency weightings must add up to 100.',
                ]);
            }

            $before = $configuration->only([
                'default_review_frequency', 'rating_scale_id', 'goal_weighting',
                'competency_weighting', 'review_visibility', 'calibration_enabled',
            ]);

            $configuration->update([
                'default_review_frequency' => $data['default_review_frequency'] ?? $configuration->default_review_frequency,
                'rating_scale_id' => $data['rating_scale_id'] ?? $configuration->rating_scale_id,
                'goal_weighting' => $goal,
                'competency_weighting' => $competency,
                'review_visibility' => $data['review_visibility'] ?? $configuration->review_visibility,
                'calibration_enabled' => (bool) ($data['calibration_enabled'] ?? $configuration->calibration_enabled),
            ]);

            $this->auditLogger->log($configuration, 'performance_configuration_updated', [
                'before' => $before,
                'after' => $configuration->only(array_keys($before)),
            ], $actor);

            event(PerformanceConfigurationUpdated::forModel($configuration, ['actor_id' => $actor->id]));

            return $configuration->fresh();
        });
    }

    // -------------------------------------------------------------------------
    // Rating Scales
    // -------------------------------------------------------------------------

    public function createRatingScale(array $data, User $actor): PerformanceRatingScale
    {
        return DB::transaction(function () use ($data, $actor): PerformanceRatingScale {
            $levels = $data['levels'] ?? config('hrms.performance.default_rating_scale_levels', []);
            unset($data['levels']);

            if ($levels === []) {
                throw ValidationException::withMessages([
                    'levels' => 'At least one rating scale level is required.',
                ]);
            }

            $this->assertUniqueLevelValues($levels);

            if (! empty($data['is_default'])) {
                $this->clearDefaultRatingScales();
            }

            $scale = PerformanceRatingScale::query()->create([
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'] ?? null,
                'is_default' => (bool) ($data['is_default'] ?? false),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $this->syncRatingScaleLevels($scale, $levels);

            $this->auditLogger->log($scale, 'performance_rating_scale_created', [
                'name' => $scale->name,
                'code' => $scale->code,
                'level_count' => count($levels),
            ], $actor);

            return $scale->fresh(['levels']);
        });
    }

    public function updateRatingScale(PerformanceRatingScale $scale, array $data, User $actor): PerformanceRatingScale
    {
        return DB::transaction(function () use ($scale, $data, $actor): PerformanceRatingScale {
            $levels = $data['levels'] ?? null;
            unset($data['levels']);

            if (is_array($levels)) {
                if ($levels === []) {
                    throw ValidationException::withMessages([
                        'levels' => 'At least one rating scale level is required.',
                    ]);
                }
                $this->assertUniqueLevelValues($levels);
            }

            if (! empty($data['is_default'])) {
                $this->clearDefaultRatingScales($scale->id);
            }

            $before = $scale->only(['name', 'code', 'description', 'is_default', 'is_active']);
            $scale->update([
                'name' => $data['name'] ?? $scale->name,
                'code' => $data['code'] ?? $scale->code,
                'description' => array_key_exists('description', $data) ? $data['description'] : $scale->description,
                'is_default' => array_key_exists('is_default', $data) ? (bool) $data['is_default'] : $scale->is_default,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $scale->is_active,
            ]);

            if (is_array($levels)) {
                $scale->levels()->delete();
                $this->syncRatingScaleLevels($scale, $levels);
            }

            $this->auditLogger->log($scale, 'performance_rating_scale_updated', [
                'before' => $before,
                'after' => $scale->only(array_keys($before)),
            ], $actor);

            return $scale->fresh(['levels']);
        });
    }

    public function deleteRatingScale(PerformanceRatingScale $scale, User $actor): void
    {
        DB::transaction(function () use ($scale, $actor): void {
            $config = PerformanceConfiguration::query()
                ->where('rating_scale_id', $scale->id)
                ->exists();

            if ($config) {
                throw ValidationException::withMessages([
                    'scale' => 'Cannot delete a rating scale that is linked to performance configuration.',
                ]);
            }

            $this->auditLogger->log($scale, 'performance_rating_scale_deleted', [
                'name' => $scale->name,
                'code' => $scale->code,
            ], $actor);

            $scale->levels()->delete();
            $scale->delete();
        });
    }

    // -------------------------------------------------------------------------
    // Competency Categories
    // -------------------------------------------------------------------------

    public function createCompetencyCategory(array $data, User $actor): CompetencyCategory
    {
        return DB::transaction(function () use ($data, $actor): CompetencyCategory {
            $category = CompetencyCategory::query()->create([
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $this->auditLogger->log($category, 'competency_category_created', [
                'name' => $category->name,
                'code' => $category->code,
            ], $actor);

            return $category;
        });
    }

    public function updateCompetencyCategory(CompetencyCategory $category, array $data, User $actor): CompetencyCategory
    {
        return DB::transaction(function () use ($category, $data, $actor): CompetencyCategory {
            $before = $category->only(['name', 'code', 'description', 'is_active']);
            $category->update([
                'name' => $data['name'] ?? $category->name,
                'code' => $data['code'] ?? $category->code,
                'description' => array_key_exists('description', $data) ? $data['description'] : $category->description,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $category->is_active,
            ]);

            $this->auditLogger->log($category, 'competency_category_updated', [
                'before' => $before,
                'after' => $category->only(array_keys($before)),
            ], $actor);

            return $category->fresh();
        });
    }

    public function deleteCompetencyCategory(CompetencyCategory $category, User $actor): void
    {
        DB::transaction(function () use ($category, $actor): void {
            if ($category->competencies()->exists()) {
                throw ValidationException::withMessages([
                    'category' => 'Cannot delete a category that still has competencies.',
                ]);
            }

            $this->auditLogger->log($category, 'competency_category_deleted', [
                'name' => $category->name,
                'code' => $category->code,
            ], $actor);

            $category->delete();
        });
    }

    // -------------------------------------------------------------------------
    // Competencies
    // -------------------------------------------------------------------------

    public function createCompetency(array $data, User $actor): Competency
    {
        return DB::transaction(function () use ($data, $actor): Competency {
            $this->assertOrgCategory((int) $data['competency_category_id']);

            $competency = Competency::query()->create([
                'competency_category_id' => $data['competency_category_id'],
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $this->auditLogger->log($competency, 'competency_created', [
                'name' => $competency->name,
                'code' => $competency->code,
                'competency_category_id' => $competency->competency_category_id,
            ], $actor);

            return $competency;
        });
    }

    public function updateCompetency(Competency $competency, array $data, User $actor): Competency
    {
        return DB::transaction(function () use ($competency, $data, $actor): Competency {
            if (! empty($data['competency_category_id'])) {
                $this->assertOrgCategory((int) $data['competency_category_id']);
            }

            $before = $competency->only(['name', 'code', 'description', 'is_active', 'competency_category_id']);
            $competency->update([
                'competency_category_id' => $data['competency_category_id'] ?? $competency->competency_category_id,
                'name' => $data['name'] ?? $competency->name,
                'code' => $data['code'] ?? $competency->code,
                'description' => array_key_exists('description', $data) ? $data['description'] : $competency->description,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $competency->is_active,
            ]);

            $this->auditLogger->log($competency, 'competency_updated', [
                'before' => $before,
                'after' => $competency->only(array_keys($before)),
            ], $actor);

            return $competency->fresh();
        });
    }

    public function deleteCompetency(Competency $competency, User $actor): void
    {
        DB::transaction(function () use ($competency, $actor): void {
            if ($competency->templateCompetencies()->exists()) {
                throw ValidationException::withMessages([
                    'competency' => 'Cannot delete a competency that is used by review templates.',
                ]);
            }

            $this->auditLogger->log($competency, 'competency_deleted', [
                'name' => $competency->name,
                'code' => $competency->code,
            ], $actor);

            $competency->delete();
        });
    }

    // -------------------------------------------------------------------------
    // Performance Cycles
    // -------------------------------------------------------------------------

    public function createCycle(array $data, User $actor): PerformanceCycle
    {
        return DB::transaction(function () use ($data, $actor): PerformanceCycle {
            [$start, $end] = $this->assertDateRange($data['start_date'], $data['end_date']);

            $cycle = PerformanceCycle::query()->create([
                'name' => $data['name'],
                'cycle_type' => $data['cycle_type'],
                'status' => $data['status'] ?? 'draft',
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'description' => $data['description'] ?? null,
            ]);

            if ($cycle->status === 'active') {
                $this->ensureSingleActiveCycle($cycle->id);
            }

            $this->auditLogger->log($cycle, 'performance_cycle_created', [
                'name' => $cycle->name,
                'cycle_type' => $cycle->cycle_type,
                'status' => $cycle->status,
            ], $actor);

            event(PerformanceCycleCreated::forModel($cycle, ['actor_id' => $actor->id]));

            if ($cycle->status === 'active') {
                event(PerformanceCycleActivated::forModel($cycle, ['actor_id' => $actor->id]));
            }

            return $cycle;
        });
    }

    public function updateCycle(PerformanceCycle $cycle, array $data, User $actor): PerformanceCycle
    {
        return DB::transaction(function () use ($cycle, $data, $actor): PerformanceCycle {
            if (in_array($cycle->status, ['closed', 'archived'], true)) {
                throw ValidationException::withMessages([
                    'cycle' => 'Closed or archived cycles cannot be updated.',
                ]);
            }

            $start = $data['start_date'] ?? $cycle->start_date->toDateString();
            $end = $data['end_date'] ?? $cycle->end_date->toDateString();
            [$startDate, $endDate] = $this->assertDateRange($start, $end);

            $before = $cycle->only(['name', 'cycle_type', 'status', 'start_date', 'end_date', 'description']);
            $newStatus = $data['status'] ?? $cycle->status;

            if ($newStatus === 'active' && $cycle->status !== 'active') {
                $this->ensureSingleActiveCycle($cycle->id);
            }

            $cycle->update([
                'name' => $data['name'] ?? $cycle->name,
                'cycle_type' => $data['cycle_type'] ?? $cycle->cycle_type,
                'status' => $newStatus,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'description' => array_key_exists('description', $data) ? $data['description'] : $cycle->description,
            ]);

            $this->auditLogger->log($cycle, 'performance_cycle_updated', [
                'before' => $before,
                'after' => $cycle->only(array_keys($before)),
            ], $actor);

            return $cycle->fresh();
        });
    }

    public function activateCycle(PerformanceCycle $cycle, User $actor): PerformanceCycle
    {
        return DB::transaction(function () use ($cycle, $actor): PerformanceCycle {
            if ($cycle->status === 'active') {
                throw ValidationException::withMessages([
                    'cycle' => 'Performance cycle is already active.',
                ]);
            }

            if (! in_array($cycle->status, ['draft', 'scheduled'], true)) {
                throw ValidationException::withMessages([
                    'cycle' => 'Only draft or scheduled cycles can be activated.',
                ]);
            }

            $this->ensureSingleActiveCycle($cycle->id);

            $before = $cycle->status;
            $cycle->update(['status' => 'active']);

            $this->auditLogger->log($cycle, 'performance_cycle_activated', [
                'before_status' => $before,
                'after_status' => 'active',
            ], $actor);

            event(PerformanceCycleActivated::forModel($cycle, ['actor_id' => $actor->id]));

            return $cycle->fresh();
        });
    }

    public function closeCycle(PerformanceCycle $cycle, User $actor): PerformanceCycle
    {
        return DB::transaction(function () use ($cycle, $actor): PerformanceCycle {
            if ($cycle->status !== 'active') {
                throw ValidationException::withMessages([
                    'cycle' => 'Only active cycles can be closed.',
                ]);
            }

            $before = $cycle->status;
            $cycle->update(['status' => 'closed']);

            $this->auditLogger->log($cycle, 'performance_cycle_closed', [
                'before_status' => $before,
                'after_status' => 'closed',
            ], $actor);

            return $cycle->fresh();
        });
    }

    public function archiveCycle(PerformanceCycle $cycle, User $actor): PerformanceCycle
    {
        return DB::transaction(function () use ($cycle, $actor): PerformanceCycle {
            if ($cycle->status !== 'closed') {
                throw ValidationException::withMessages([
                    'cycle' => 'Only closed cycles can be archived.',
                ]);
            }

            $before = $cycle->status;
            $cycle->update(['status' => 'archived']);

            $this->auditLogger->log($cycle, 'performance_cycle_archived', [
                'before_status' => $before,
                'after_status' => 'archived',
            ], $actor);

            return $cycle->fresh();
        });
    }

    public function deleteCycle(PerformanceCycle $cycle, User $actor): void
    {
        DB::transaction(function () use ($cycle, $actor): void {
            if (! in_array($cycle->status, ['draft', 'scheduled'], true)) {
                throw ValidationException::withMessages([
                    'cycle' => 'Only draft or scheduled cycles can be deleted.',
                ]);
            }

            $this->auditLogger->log($cycle, 'performance_cycle_deleted', [
                'name' => $cycle->name,
                'status' => $cycle->status,
            ], $actor);

            $cycle->delete();
        });
    }

    public function resolveActiveCycle(): ?PerformanceCycle
    {
        return PerformanceCycle::query()
            ->where('status', 'active')
            ->orderByDesc('start_date')
            ->first();
    }

    // -------------------------------------------------------------------------
    // Review Templates
    // -------------------------------------------------------------------------

    public function createTemplate(array $data, User $actor): PerformanceReviewTemplate
    {
        return DB::transaction(function () use ($data, $actor): PerformanceReviewTemplate {
            $sections = $data['sections'] ?? [];
            $competencies = $data['competencies'] ?? [];
            unset($data['sections'], $data['competencies']);

            $template = PerformanceReviewTemplate::query()->create([
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'] ?? null,
                'instructions' => $data['instructions'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $sectionIds = $this->syncTemplateSections($template, $sections);
            $this->syncTemplateCompetencies($template, $competencies, $sectionIds);

            $this->auditLogger->log($template, 'performance_template_created', [
                'name' => $template->name,
                'code' => $template->code,
                'section_count' => count($sections),
                'competency_count' => count($competencies),
            ], $actor);

            event(PerformanceTemplateCreated::forModel($template, ['actor_id' => $actor->id]));

            return $template->fresh(['sections', 'templateCompetencies.competency']);
        });
    }

    public function updateTemplate(PerformanceReviewTemplate $template, array $data, User $actor): PerformanceReviewTemplate
    {
        return DB::transaction(function () use ($template, $data, $actor): PerformanceReviewTemplate {
            $sections = $data['sections'] ?? null;
            $competencies = $data['competencies'] ?? null;
            unset($data['sections'], $data['competencies']);

            $before = $template->only(['name', 'code', 'description', 'instructions', 'is_active']);
            $template->update([
                'name' => $data['name'] ?? $template->name,
                'code' => $data['code'] ?? $template->code,
                'description' => array_key_exists('description', $data) ? $data['description'] : $template->description,
                'instructions' => array_key_exists('instructions', $data) ? $data['instructions'] : $template->instructions,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $template->is_active,
            ]);

            $sectionIds = [];
            if (is_array($sections)) {
                $template->templateCompetencies()->delete();
                $template->sections()->delete();
                $sectionIds = $this->syncTemplateSections($template, $sections);
            }

            if (is_array($competencies)) {
                if (! is_array($sections)) {
                    $template->templateCompetencies()->delete();
                    $sectionIds = $template->sections()->pluck('id', 'name')->all();
                }
                $this->syncTemplateCompetencies($template, $competencies, $sectionIds);
            }

            $this->auditLogger->log($template, 'performance_template_updated', [
                'before' => $before,
                'after' => $template->only(array_keys($before)),
            ], $actor);

            return $template->fresh(['sections', 'templateCompetencies.competency']);
        });
    }

    public function deleteTemplate(PerformanceReviewTemplate $template, User $actor): void
    {
        DB::transaction(function () use ($template, $actor): void {
            $this->auditLogger->log($template, 'performance_template_deleted', [
                'name' => $template->name,
                'code' => $template->code,
            ], $actor);

            $template->templateCompetencies()->delete();
            $template->sections()->delete();
            $template->delete();
        });
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @param array<int, array<string, mixed>> $levels */
    protected function syncRatingScaleLevels(PerformanceRatingScale $scale, array $levels): void
    {
        foreach (array_values($levels) as $index => $level) {
            PerformanceRatingScaleLevel::query()->create([
                'organization_id' => $scale->organization_id,
                'rating_scale_id' => $scale->id,
                'value' => (int) $level['value'],
                'label' => $level['label'],
                'description' => $level['description'] ?? null,
                'sort_order' => (int) ($level['sort_order'] ?? $index),
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     * @return array<string, int>
     */
    protected function syncTemplateSections(PerformanceReviewTemplate $template, array $sections): array
    {
        $map = [];

        foreach (array_values($sections) as $index => $section) {
            $created = PerformanceReviewTemplateSection::query()->create([
                'organization_id' => $template->organization_id,
                'review_template_id' => $template->id,
                'name' => $section['name'],
                'instructions' => $section['instructions'] ?? null,
                'weightage' => $section['weightage'] ?? 0,
                'sort_order' => (int) ($section['sort_order'] ?? $index),
            ]);
            $map[$created->name] = $created->id;
            if (isset($section['key'])) {
                $map[(string) $section['key']] = $created->id;
            }
        }

        return $map;
    }

    /**
     * @param  array<int, array<string, mixed>>  $competencies
     * @param  array<string, int>  $sectionIds
     */
    protected function syncTemplateCompetencies(
        PerformanceReviewTemplate $template,
        array $competencies,
        array $sectionIds,
    ): void {
        foreach (array_values($competencies) as $index => $row) {
            $this->assertOrgCompetency((int) $row['competency_id']);

            $sectionId = null;
            if (! empty($row['section_id'])) {
                $sectionId = (int) $row['section_id'];
            } elseif (! empty($row['section_key']) && isset($sectionIds[$row['section_key']])) {
                $sectionId = $sectionIds[$row['section_key']];
            } elseif (! empty($row['section_name']) && isset($sectionIds[$row['section_name']])) {
                $sectionId = $sectionIds[$row['section_name']];
            }

            PerformanceReviewTemplateCompetency::query()->create([
                'organization_id' => $template->organization_id,
                'review_template_id' => $template->id,
                'section_id' => $sectionId,
                'competency_id' => $row['competency_id'],
                'weightage' => $row['weightage'] ?? 0,
                'sort_order' => (int) ($row['sort_order'] ?? $index),
            ]);
        }
    }

    /** @param array<int, array<string, mixed>> $levels */
    protected function assertUniqueLevelValues(array $levels): void
    {
        $values = array_map(fn ($level) => (int) $level['value'], $levels);
        if (count($values) !== count(array_unique($values))) {
            throw ValidationException::withMessages([
                'levels' => 'Rating scale level values must be unique.',
            ]);
        }
    }

    protected function clearDefaultRatingScales(?int $exceptId = null): void
    {
        $query = PerformanceRatingScale::query()->where('is_default', true);
        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }
        $query->update(['is_default' => false]);
    }

    protected function ensureSingleActiveCycle(?int $exceptId = null): void
    {
        $query = PerformanceCycle::query()->where('status', 'active');
        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        $existing = $query->first();
        if ($existing) {
            throw ValidationException::withMessages([
                'status' => 'Another performance cycle is already active. Close it before activating a new one.',
            ]);
        }
    }

    /** @return array{0: Carbon, 1: Carbon} */
    protected function assertDateRange(string $start, string $end): array
    {
        $startDate = Carbon::parse($start)->startOfDay();
        $endDate = Carbon::parse($end)->startOfDay();

        if ($endDate->lt($startDate)) {
            throw ValidationException::withMessages([
                'end_date' => 'End date must be on or after the start date.',
            ]);
        }

        return [$startDate, $endDate];
    }

    protected function assertOrgRatingScale(int $id): void
    {
        $exists = PerformanceRatingScale::query()->whereKey($id)->where('is_active', true)->exists();
        if (! $exists) {
            throw ValidationException::withMessages([
                'rating_scale_id' => 'Selected rating scale is invalid for this organization.',
            ]);
        }
    }

    protected function assertOrgCategory(int $id): void
    {
        $exists = CompetencyCategory::query()->whereKey($id)->exists();
        if (! $exists) {
            throw ValidationException::withMessages([
                'competency_category_id' => 'Selected competency category is invalid for this organization.',
            ]);
        }
    }

    protected function assertOrgCompetency(int $id): void
    {
        $exists = Competency::query()->whereKey($id)->exists();
        if (! $exists) {
            throw ValidationException::withMessages([
                'competency_id' => 'Selected competency is invalid for this organization.',
            ]);
        }
    }
}
