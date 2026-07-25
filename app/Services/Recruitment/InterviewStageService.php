<?php

namespace App\Services\Recruitment;

use App\Models\InterviewStage;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InterviewStageService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function ensureDefaultStages(Organization $organization, ?User $actor = null): void
    {
        if (InterviewStage::query()->where('organization_id', $organization->id)->exists()) {
            return;
        }

        $defaults = config('hrms.recruitment.default_interview_stages', []);
        $order = 0;

        DB::transaction(function () use ($organization, $actor, $defaults, &$order): void {
            foreach ($defaults as $slug => $name) {
                InterviewStage::query()->create([
                    'organization_id' => $organization->id,
                    'slug' => $slug,
                    'name' => $name,
                    'sort_order' => $order++,
                    'is_default' => true,
                    'is_active' => true,
                    'created_by' => $actor?->id,
                    'updated_by' => $actor?->id,
                ]);
            }
        });
    }

    public function createStage(array $data, User $actor): InterviewStage
    {
        $this->assertUniqueSlug((int) $data['organization_id'], (string) $data['slug']);

        return DB::transaction(function () use ($data, $actor): InterviewStage {
            $stage = InterviewStage::query()->create(array_merge($data, [
                'is_default' => $data['is_default'] ?? false,
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]));

            $this->auditLogger->log($stage, 'interview_stage_created', [
                'slug' => $stage->slug,
                'name' => $stage->name,
            ], $actor);

            return $stage;
        });
    }

    public function updateStage(InterviewStage $stage, array $data, User $actor): InterviewStage
    {
        if (isset($data['slug']) && $data['slug'] !== $stage->slug) {
            $this->assertUniqueSlug((int) $stage->organization_id, (string) $data['slug'], $stage->id);
        }

        return DB::transaction(function () use ($stage, $data, $actor): InterviewStage {
            $before = $stage->only(['name', 'slug', 'sort_order', 'is_active']);

            $stage->update(array_merge($data, ['updated_by' => $actor->id]));
            $stage->refresh();

            $this->auditLogger->log($stage, 'interview_stage_updated', [
                'before' => $before,
                'after' => $stage->only(array_keys($before)),
            ], $actor);

            return $stage;
        });
    }

    public function deleteStage(InterviewStage $stage, User $actor): void
    {
        if ($stage->is_default) {
            throw ValidationException::withMessages([
                'stage' => 'Default interview stages cannot be deleted.',
            ]);
        }

        if ($stage->interviewRounds()->exists()) {
            throw ValidationException::withMessages([
                'stage' => 'Interview stages in use cannot be deleted.',
            ]);
        }

        DB::transaction(function () use ($stage, $actor): void {
            $this->auditLogger->log($stage, 'interview_stage_deleted', [
                'slug' => $stage->slug,
                'name' => $stage->name,
            ], $actor);
            $stage->delete();
        });
    }

    public function assertValidStageProgression(InterviewStage $from, InterviewStage $to): void
    {
        if ((int) $from->organization_id !== (int) $to->organization_id) {
            throw ValidationException::withMessages([
                'interview_stage_id' => 'Interview stages must belong to the same organization.',
            ]);
        }

        $terminal = ['rejected', 'withdrawn', 'hired'];

        if (in_array($from->slug, $terminal, true) && $from->slug !== $to->slug) {
            throw ValidationException::withMessages([
                'interview_stage_id' => 'Applications in a terminal stage cannot move to another stage.',
            ]);
        }

        if ($to->sort_order < $from->sort_order && ! in_array($to->slug, $terminal, true)) {
            throw ValidationException::withMessages([
                'interview_stage_id' => 'Stage progression must follow the configured sequence.',
            ]);
        }
    }

    protected function assertUniqueSlug(int $organizationId, string $slug, ?int $exceptId = null): void
    {
        $query = InterviewStage::query()
            ->where('organization_id', $organizationId)
            ->where('slug', $slug);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'slug' => 'An interview stage with this slug already exists.',
            ]);
        }
    }
}
