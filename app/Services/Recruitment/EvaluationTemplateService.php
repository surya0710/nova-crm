<?php

namespace App\Services\Recruitment;

use App\Models\EvaluationQuestion;
use App\Models\EvaluationSection;
use App\Models\EvaluationTemplate;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EvaluationTemplateService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function createTemplate(array $data, User $actor): EvaluationTemplate
    {
        return DB::transaction(function () use ($data, $actor): EvaluationTemplate {
            $template = EvaluationTemplate::query()->create([
                'organization_id' => $data['organization_id'],
                'name' => $data['name'],
                'department_id' => $data['department_id'] ?? null,
                'designation_id' => $data['designation_id'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            if (! empty($data['sections'])) {
                $this->syncSections($template, $data['sections'], $actor);
            }

            $this->auditLogger->log($template, 'evaluation_template_created', [
                'name' => $template->name,
            ], $actor);

            return $template->load('sections.questions');
        });
    }

    public function updateTemplate(EvaluationTemplate $template, array $data, User $actor): EvaluationTemplate
    {
        return DB::transaction(function () use ($template, $data, $actor): EvaluationTemplate {
            $before = $template->only(['name', 'department_id', 'designation_id', 'is_active']);

            $template->update(array_merge($data, ['updated_by' => $actor->id]));
            $template->refresh();

            if (array_key_exists('sections', $data)) {
                $this->syncSections($template, $data['sections'] ?? [], $actor);
            }

            $this->auditLogger->log($template, 'evaluation_template_updated', [
                'before' => $before,
                'after' => $template->only(array_keys($before)),
            ], $actor);

            return $template->load('sections.questions');
        });
    }

    public function deleteTemplate(EvaluationTemplate $template, User $actor): void
    {
        if ($template->interviewRounds()->exists() || $template->evaluations()->exists()) {
            throw ValidationException::withMessages([
                'template' => 'Evaluation templates in use cannot be deleted.',
            ]);
        }

        DB::transaction(function () use ($template, $actor): void {
            $this->auditLogger->log($template, 'evaluation_template_deleted', [
                'name' => $template->name,
            ], $actor);
            $template->delete();
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     */
    protected function syncSections(EvaluationTemplate $template, array $sections, User $actor): void
    {
        $template->sections()->delete();

        foreach ($sections as $sectionIndex => $sectionData) {
            $section = EvaluationSection::query()->create([
                'organization_id' => $template->organization_id,
                'evaluation_template_id' => $template->id,
                'title' => $sectionData['title'],
                'weight' => $sectionData['weight'] ?? 1,
                'sort_order' => $sectionData['sort_order'] ?? $sectionIndex,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            foreach ($sectionData['questions'] ?? [] as $questionIndex => $questionData) {
                $this->assertValidQuestionType($questionData['question_type'] ?? '');

                EvaluationQuestion::query()->create([
                    'organization_id' => $template->organization_id,
                    'evaluation_section_id' => $section->id,
                    'question' => $questionData['question'],
                    'question_type' => $questionData['question_type'],
                    'is_required' => $questionData['is_required'] ?? true,
                    'weight' => $questionData['weight'] ?? 1,
                    'sort_order' => $questionData['sort_order'] ?? $questionIndex,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);
            }
        }
    }

    protected function assertValidQuestionType(string $type): void
    {
        if (! in_array($type, array_keys(config('hrms.recruitment.evaluation_question_types', [])), true)) {
            throw ValidationException::withMessages([
                'question_type' => 'Invalid evaluation question type.',
            ]);
        }
    }
}
