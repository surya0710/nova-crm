<?php

namespace App\Services\Recruitment;

use App\Events\InterviewCancelled;
use App\Events\InterviewCompleted;
use App\Events\InterviewScheduled;
use App\Models\InterviewRound;
use App\Models\InterviewStage;
use App\Models\JobApplication;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InterviewRoundService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected NotificationService $notificationService,
        protected InterviewStageService $stageService,
        protected InterviewParticipantService $participantService,
        protected InterviewMeetingService $meetingService,
    ) {}

    public function createRound(array $data, User $actor): InterviewRound
    {
        $application = JobApplication::query()->findOrFail($data['job_application_id']);
        $this->assertApplicationEligible($application);

        $stage = InterviewStage::query()->findOrFail($data['interview_stage_id']);
        $this->stageService->assertValidStageProgression(
            $this->resolveCurrentStage($application),
            $stage,
        );

        return DB::transaction(function () use ($data, $application, $stage, $actor): InterviewRound {
            $roundNumber = $data['round_number'] ?? $this->nextRoundNumber(
                (int) $application->id,
                (int) $stage->id,
            );

            $round = InterviewRound::query()->create([
                'organization_id' => $application->organization_id,
                'job_application_id' => $application->id,
                'interview_stage_id' => $stage->id,
                'round_number' => $roundNumber,
                'interview_type' => $data['interview_type'],
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'location' => $data['location'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'evaluation_template_id' => $data['evaluation_template_id'] ?? null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $meetingFields = $this->applyMeetingProvider($round, $data);
            if ($meetingFields !== []) {
                $round->update($meetingFields);
                $round->refresh();
            }

            $this->auditLogger->log($round, 'interview_round_created', [
                'job_application_id' => $round->job_application_id,
                'interview_stage_id' => $round->interview_stage_id,
                'round_number' => $round->round_number,
                'meeting_provider' => $round->meeting_provider,
            ], $actor);

            if (! empty($data['participants'])) {
                $this->participantService->syncParticipants($round, $data['participants'], $actor);
            }

            if ($round->status === 'scheduled') {
                event(InterviewScheduled::forModel($round->fresh(), ['actor_id' => $actor->id]));
                $this->notifyInterviewScheduled($round->fresh(['participants.employee.user', 'jobApplication.candidate', 'jobApplication.jobOpening']));
            }

            return $round->load(['interviewStage', 'jobApplication.candidate', 'participants.employee']);
        });
    }

    public function updateRound(InterviewRound $round, array $data, User $actor): InterviewRound
    {
        if (in_array($round->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Completed or cancelled interview rounds cannot be edited.',
            ]);
        }

        return DB::transaction(function () use ($round, $data, $actor): InterviewRound {
            $before = $round->only(['scheduled_at', 'duration_minutes', 'location', 'notes', 'status', 'evaluation_template_id', 'meeting_provider', 'meeting_link']);
            $wasScheduled = $round->status === 'scheduled';

            if (isset($data['interview_stage_id'])) {
                $stage = InterviewStage::query()->findOrFail($data['interview_stage_id']);
                $this->stageService->assertValidStageProgression(
                    $round->interviewStage,
                    $stage,
                );
            }

            $meetingFields = $this->applyMeetingProvider($round, $data);
            $round->update(array_merge($data, $meetingFields, ['updated_by' => $actor->id]));
            $round->refresh();

            if (! empty($data['participants'])) {
                $this->participantService->syncParticipants($round, $data['participants'], $actor);
            }

            $this->auditLogger->log($round, 'interview_round_updated', [
                'before' => $before,
                'after' => $round->only(array_keys($before)),
            ], $actor);

            if ($round->status === 'scheduled' && (! $wasScheduled || isset($data['scheduled_at']) || isset($data['meeting_provider']))) {
                event(InterviewScheduled::forModel($round, ['actor_id' => $actor->id]));
                $this->notifyScheduleChanged($round->load(['participants.employee.user', 'jobApplication.candidate']));
            }

            return $round->load(['interviewStage', 'jobApplication.candidate', 'participants.employee']);
        });
    }

    public function scheduleRound(InterviewRound $round, array $data, User $actor): InterviewRound
    {
        return $this->updateRound($round, array_merge($data, ['status' => 'scheduled']), $actor);
    }

    public function completeRound(InterviewRound $round, User $actor): InterviewRound
    {
        $this->assertRequiredEvaluationsSubmitted($round);

        return DB::transaction(function () use ($round, $actor): InterviewRound {
            $round->update(['status' => 'completed', 'updated_by' => $actor->id]);
            $round->refresh();

            $this->auditLogger->log($round, 'interview_round_completed', [
                'job_application_id' => $round->job_application_id,
            ], $actor);

            event(InterviewCompleted::forModel($round, ['actor_id' => $actor->id]));

            return $round->load(['interviewStage', 'evaluations.participant']);
        });
    }

    public function cancelRound(InterviewRound $round, User $actor, ?string $reason = null): InterviewRound
    {
        if ($round->status === 'completed') {
            throw ValidationException::withMessages([
                'status' => 'Completed interview rounds cannot be cancelled.',
            ]);
        }

        return DB::transaction(function () use ($round, $actor, $reason): InterviewRound {
            $organization = Organization::query()->find($round->organization_id);
            if ($organization) {
                $this->meetingService->cancelForRound($round, $organization);
            }

            $round->update(['status' => 'cancelled', 'updated_by' => $actor->id]);
            $round->refresh();

            $this->auditLogger->log($round, 'interview_round_cancelled', [
                'reason' => $reason,
            ], $actor);

            event(InterviewCancelled::forModel($round, ['actor_id' => $actor->id, 'reason' => $reason]));

            return $round;
        });
    }

    public function deleteRound(InterviewRound $round, User $actor): void
    {
        if ($round->status === 'completed') {
            throw ValidationException::withMessages([
                'status' => 'Completed interview rounds cannot be deleted.',
            ]);
        }

        DB::transaction(function () use ($round, $actor): void {
            $this->auditLogger->log($round, 'interview_round_deleted', [
                'job_application_id' => $round->job_application_id,
            ], $actor);
            $round->delete();
        });
    }

    protected function assertApplicationEligible(JobApplication $application): void
    {
        if (in_array($application->stage, ['rejected', 'withdrawn'], true)) {
            throw ValidationException::withMessages([
                'job_application_id' => 'Rejected or withdrawn applications cannot receive new interview rounds.',
            ]);
        }

        if ($application->status === 'closed' && in_array($application->stage, ['rejected', 'withdrawn', 'hired'], true)) {
            throw ValidationException::withMessages([
                'job_application_id' => 'Closed applications cannot receive new interview rounds.',
            ]);
        }
    }

    protected function assertRequiredEvaluationsSubmitted(InterviewRound $round): void
    {
        $round->load(['participants.evaluation', 'evaluationTemplate.sections.questions']);

        $participants = $round->participants;

        if ($participants->isEmpty()) {
            throw ValidationException::withMessages([
                'evaluations' => 'Interview cannot be completed without assigned interviewers.',
            ]);
        }

        foreach ($participants as $participant) {
            $evaluation = $participant->evaluation;

            if (! $evaluation || $evaluation->status !== 'submitted') {
                throw ValidationException::withMessages([
                    'evaluations' => 'All assigned interviewers must submit evaluations before completing the interview.',
                ]);
            }
        }
    }

    protected function resolveCurrentStage(JobApplication $application): InterviewStage
    {
        $latestRound = $application->interviewRounds()->latest('id')->first();

        if ($latestRound) {
            return $latestRound->interviewStage;
        }

        $slug = match ($application->stage) {
            'applied' => 'applied',
            'screening' => 'screening',
            'interview', 'evaluation' => 'technical_interview',
            'offer' => 'offer',
            'hired' => 'hired',
            'rejected' => 'rejected',
            'withdrawn' => 'withdrawn',
            default => 'applied',
        };

        return InterviewStage::query()
            ->where('organization_id', $application->organization_id)
            ->where('slug', $slug)
            ->firstOrFail();
    }

    protected function nextRoundNumber(int $applicationId, int $stageId): int
    {
        $max = InterviewRound::query()
            ->where('job_application_id', $applicationId)
            ->where('interview_stage_id', $stageId)
            ->max('round_number');

        return ((int) $max) + 1;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function applyMeetingProvider(InterviewRound $round, array $data): array
    {
        if (! array_key_exists('meeting_provider', $data) && empty($data['meeting_link']) && empty($data['custom_url'])) {
            return [];
        }

        $organization = Organization::query()->find($round->organization_id);
        if (! $organization) {
            return [];
        }

        $generated = $this->meetingService->generateForRound($round, $organization, $data);

        return array_filter([
            'meeting_link' => $generated['meeting_link'],
            'meeting_provider' => $generated['meeting_provider'],
            'meeting_id' => $generated['meeting_id'],
            'join_instructions' => $generated['join_instructions'],
        ], fn ($value) => $value !== null);
    }

    protected function notifyInterviewScheduled(InterviewRound $round): void
    {
        $title = 'Interview assigned';
        $candidateName = $round->jobApplication?->candidate?->fullName() ?? 'Candidate';
        $message = sprintf('You have been assigned to interview %s.', $candidateName);
        if ($round->meeting_link) {
            $message .= ' Meeting: '.$round->meeting_link;
            if ($round->join_instructions) {
                $message .= ' — '.$round->join_instructions;
            }
        }
        $url = '/hrms/recruitment/interview-rounds/'.$round->id;

        $this->notifyParticipants($round, $title, $message, $url);
        $this->notifyRecruiter($round, $title, $message, $url);
    }

    protected function notifyScheduleChanged(InterviewRound $round): void
    {
        $title = 'Interview schedule changed';
        $candidateName = $round->jobApplication?->candidate?->fullName() ?? 'Candidate';
        $message = sprintf('The interview schedule for %s has been updated.', $candidateName);
        if ($round->meeting_link) {
            $message .= ' Meeting: '.$round->meeting_link;
        }
        $url = '/hrms/recruitment/interview-rounds/'.$round->id;

        $this->notifyParticipants($round, $title, $message, $url);
        $this->notifyRecruiter($round, $title, $message, $url);
    }

    protected function notifyParticipants(InterviewRound $round, string $title, string $message, string $url): void
    {
        foreach ($round->participants as $participant) {
            $userId = $participant->employee?->user_id;

            if (! $userId) {
                continue;
            }

            try {
                $this->notificationService->send(
                    (int) $round->organization_id,
                    (int) $userId,
                    $title,
                    $message,
                    $url,
                );
            } catch (ValidationException) {
                // Skip when recipient is not an organization member.
            }
        }
    }

    protected function notifyRecruiter(InterviewRound $round, string $title, string $message, string $url): void
    {
        $recipientId = $round->jobApplication?->assigned_recruiter_id ?? $round->created_by;

        if (! $recipientId) {
            return;
        }

        try {
            $this->notificationService->send(
                (int) $round->organization_id,
                (int) $recipientId,
                $title,
                $message,
                $url,
            );
        } catch (ValidationException) {
            // Skip when recipient is not an organization member.
        }
    }
}
