<?php

namespace App\Services\Recruitment;

use App\Models\OfferTemplate;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OfferTemplateService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function createTemplate(array $data, User $actor): OfferTemplate
    {
        return DB::transaction(function () use ($data, $actor): OfferTemplate {
            $template = OfferTemplate::query()->create([
                'organization_id' => $data['organization_id'],
                'name' => $data['name'],
                'department_id' => $data['department_id'] ?? null,
                'designation_id' => $data['designation_id'] ?? null,
                'employment_type' => $data['employment_type'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'template_content' => $data['template_content'],
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->auditLogger->log($template, 'offer_template_created', [
                'name' => $template->name,
            ], $actor);

            return $template->load(['department', 'designation']);
        });
    }

    public function updateTemplate(OfferTemplate $template, array $data, User $actor): OfferTemplate
    {
        return DB::transaction(function () use ($template, $data, $actor): OfferTemplate {
            $before = $template->only([
                'name', 'department_id', 'designation_id', 'employment_type', 'is_active', 'template_content',
            ]);

            $template->update(array_merge($data, ['updated_by' => $actor->id]));
            $template->refresh();

            $this->auditLogger->log($template, 'offer_template_updated', [
                'before' => $before,
                'after' => $template->only(array_keys($before)),
            ], $actor);

            return $template->load(['department', 'designation']);
        });
    }

    public function deleteTemplate(OfferTemplate $template, User $actor): void
    {
        if ($template->offerLetters()->whereNotIn('status', ['rejected', 'expired', 'withdrawn'])->exists()) {
            throw ValidationException::withMessages([
                'template' => 'Offer templates with active offers cannot be deleted.',
            ]);
        }

        DB::transaction(function () use ($template, $actor): void {
            $this->auditLogger->log($template, 'offer_template_deleted', [
                'name' => $template->name,
            ], $actor);
            $template->delete();
        });
    }
}
