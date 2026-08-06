<?php

namespace App\Services\Recruitment;

use App\Events\CandidateRecommended;
use App\Events\EvaluationSubmitted;
use App\Models\CandidateEvaluation;
use App\Models\EvaluationQuestion;
use App\Models\EvaluationResponse;
use App\Models\EvaluationTemplate;
use App\Models\InterviewParticipant;
use App\Models\InterviewRound;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CandidateEvaluationService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected NotificationService $notificationService,
    ) {}

    public function submitEvaluation(array $data, User $actor): CandidateEvaluation
    {
        $round = InterviewRound::query()->findOrFail($data['interview_round_id']);
        $participant = InterviewParticipant::query()->findOrFail($data['interview_participant_id']);

        $this->assertParticipantBelongsToRound($round, $participant);
        $this->assertUniqueEvaluation($round, $participant);

        $template = $this->resolveTemplate($round, $data['evaluation_template_id'] ?? null);

        return DB::transaction(function () use ($data, $round, $participant, $template, $actor): CandidateEvaluation {
            $evaluation = CandidateEvaluation::query()->create([
                'organization_id' => $round->organization_id,
                'interview_round_id' => $round->id,
                'interview_participant_id' => $participant->id,
                'evaluation_template_id' => $template?->id,
                'overall_rating' => $data['overall_rating'] ?? null,
                'recommendation' => $data['recommendation'] ?? null,
                'strengths' => $data['strengths'] ?? null,
                'concerns' => $data['concerns'] ?? null,
                'summary' => $data['summary'] ?? null,
                'status' => 'submitted',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            if ($template && ! empty($data['responses'])) {
                $this->storeResponses($evaluation, $template, $data['responses'], $actor);
            }

            $this->auditLogger->log($evaluation, 'candidate_evaluation_submitted', [
                'interview_round_id' => $round->id,
                'recommendation' => $evaluation->recommendation,
            ], $actor);

            event(EvaluationSubmitted::forModel($evaluation, ['actor_id' => $actor->id]));

            if (in_array($evaluation->recommendation, ['strong_hire', 'hire'], true)) {
                event(CandidateRecommended::forModel($evaluation, [
                    'actor_id' => $actor->id,
                    'recommendation' => $evaluation->recommendation,
                ]));
            }

            $this->notifyEvaluationCompleted($evaluation->load(['interviewRound.jobApplication', 'participant.employee']));

            return $evaluation->load(['responses.question', 'participant.employee']);
        });
    }

    public function updateEvaluation(CandidateEvaluation $evaluation, array $data, User $actor): CandidateEvaluation
    {
        if ($evaluation->status === 'submitted' && ($evaluation->interviewRound?->status === 'completed')) {
            throw ValidationException::withMessages([
                'evaluation' => 'Evaluations for completed interviews cannot be edited.',
            ]);
        }

        return DB::transaction(function () use ($evaluation, $data, $actor): CandidateEvaluation {
            $before = $evaluation->only(['overall_rating', 'recommendation', 'strengths', 'concerns', 'summary', 'status']);

            $evaluation->update(array_merge($data, [
                'status' => $data['status'] ?? 'submitted',
                'updated_by' => $actor->id,
            ]));
            $evaluation->refresh();

            $template = $evaluation->evaluationTemplate;

            if ($template && ! empty($data['responses'])) {
                $evaluation->responses()->delete();
                $this->storeResponses($evaluation, $template, $data['responses'], $actor);
            }

            $this->auditLogger->log($evaluation, 'candidate_evaluation_updated', [
                'before' => $before,
                'after' => $evaluation->only(array_keys($before)),
            ], $actor);

            return $evaluation->load(['responses.question', 'participant.employee']);
        });
    }

    /**
     * @param  array<int|string, mixed>  $responses
     */
    protected function storeResponses(
        CandidateEvaluation $evaluation,
        EvaluationTemplate $template,
        array $responses,
        User $actor,
    ): void {
        $questions = $template->sections()
            ->with('questions')
            ->get()
            ->flatMap(fn ($section) => $section->questions);

        foreach ($questions as $question) {
            $value = $responses[$question->id] ?? $responses[(string) $question->id] ?? null;

            if ($question->is_required && ($value === null || $value === '')) {
                throw ValidationException::withMessages([
                    'responses.'.$question->id => 'This question is required.',
                ]);
            }

            if ($value === null || $value === '') {
                continue;
            }

            $this->assertValidResponseValue($question, $value);

            EvaluationResponse::query()->create([
                'organization_id' => $evaluation->organization_id,
                'candidate_evaluation_id' => $evaluation->id,
                'evaluation_question_id' => $question->id,
                'response_value' => is_bool($value) ? ($value ? 'yes' : 'no') : (string) $value,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
        }
    }

    protected function assertValidResponseValue(EvaluationQuestion $question, mixed $value): void
    {
        match ($question->question_type) {
            'rating_1_5' => $this->assertNumericRange($value, 1, 5, $question->id),
            'rating_1_10' => $this->assertNumericRange($value, 1, 10, $question->id),
            'yes_no' => $this->assertYesNo($value, $question->id),
            'text', 'multiline' => is_string($value) || is_numeric($value)
                ? null
                : throw ValidationException::withMessages([
                    'responses.'.$question->id => 'Text response required.',
                ]),
            default => throw ValidationException::withMessages([
                'responses.'.$question->id => 'Unsupported question type.',
            ]),
        };
    }

    protected function assertNumericRange(mixed $value, int $min, int $max, int $questionId): void
    {
        if (! is_numeric($value) || (float) $value < $min || (float) $value > $max) {
            throw ValidationException::withMessages([
                'responses.'.$questionId => sprintf('Rating must be between %d and %d.', $min, $max),
            ]);
        }
    }

    protected function assertYesNo(mixed $value, int $questionId): void
    {
        $normalized = is_bool($value) ? ($value ? 'yes' : 'no') : strtolower((string) $value);

        if (! in_array($normalized, ['yes', 'no', '1', '0', 'true', 'false'], true)) {
            throw ValidationException::withMessages([
                'responses.'.$questionId => 'Yes/No response required.',
            ]);
        }
    }

    protected function assertParticipantBelongsToRound(InterviewRound $round, InterviewParticipant $participant): void
    {
        if ((int) $round->id !== (int) $participant->interview_round_id) {
            throw ValidationException::withMessages([
                'interview_participant_id' => 'Participant does not belong to this interview round.',
            ]);
        }
    }

    protected function assertUniqueEvaluation(InterviewRound $round, InterviewParticipant $participant): void
    {
        $exists = CandidateEvaluation::query()
            ->where('organization_id', $round->organization_id)
            ->where('interview_round_id', $round->id)
            ->where('interview_participant_id', $participant->id)
            ->where('status', 'submitted')
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'interview_participant_id' => 'This interviewer has already submitted an evaluation for this round.',
            ]);
        }
    }

    protected function resolveTemplate(InterviewRound $round, ?int $templateId): ?EvaluationTemplate
    {
        $id = $templateId ?? $round->evaluation_template_id;

        if (! $id) {
            return null;
        }

        return EvaluationTemplate::query()
            ->where('organization_id', $round->organization_id)
            ->where('id', $id)
            ->firstOrFail();
    }

    protected function notifyEvaluationCompleted(CandidateEvaluation $evaluation): void
    {
        $round = $evaluation->interviewRound;
        $recipientId = $round?->jobApplication?->assigned_recruiter_id ?? $round?->created_by;

        if (! $recipientId) {
            return;
        }

        $interviewer = $evaluation->participant?->displayName() ?? 'An interviewer';

        try {
            $this->notificationService->send(
                (int) $evaluation->organization_id,
                (int) $recipientId,
                'Evaluation completed',
                sprintf('%s submitted an interview evaluation.', $interviewer),
                '/hrms/recruitment/evaluations/'.$evaluation->id,
            );
        } catch (ValidationException) {
            // Skip when recipient is not an organization member.
        }
    }
}
