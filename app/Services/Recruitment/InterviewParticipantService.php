<?php

namespace App\Services\Recruitment;

use App\Models\Employee;
use App\Models\InterviewParticipant;
use App\Models\InterviewRound;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InterviewParticipantService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected NotificationService $notificationService,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $participants
     */
    public function syncParticipants(InterviewRound $round, array $participants, User $actor): void
    {
        DB::transaction(function () use ($round, $participants, $actor): void {
            $round->participants()->delete();

            foreach ($participants as $participantData) {
                $this->createParticipant($round, $participantData, $actor, audit: false);
            }

            $this->auditLogger->log($round, 'interview_participants_synced', [
                'count' => count($participants),
            ], $actor);
        });
    }

    public function assignParticipant(InterviewRound $round, array $data, User $actor): InterviewParticipant
    {
        return DB::transaction(function () use ($round, $data, $actor): InterviewParticipant {
            $participant = $this->createParticipant($round, $data, $actor);

            $this->notifyParticipantAssigned($round, $participant);

            return $participant;
        });
    }

    public function updateParticipant(InterviewParticipant $participant, array $data, User $actor): InterviewParticipant
    {
        if ($participant->interviewRound?->status === 'completed') {
            throw ValidationException::withMessages([
                'participant' => 'Participants on completed interviews cannot be modified.',
            ]);
        }

        return DB::transaction(function () use ($participant, $data, $actor): InterviewParticipant {
            $this->validateParticipantData($data, (int) $participant->organization_id);

            $before = $participant->only(['participant_type', 'employee_id', 'name', 'email', 'role']);

            $participant->update(array_merge($data, ['updated_by' => $actor->id]));
            $participant->refresh();

            $this->auditLogger->log($participant, 'interview_participant_updated', [
                'before' => $before,
                'after' => $participant->only(array_keys($before)),
            ], $actor);

            return $participant->load('employee');
        });
    }

    public function removeParticipant(InterviewParticipant $participant, User $actor): void
    {
        if ($participant->evaluation()->where('status', 'submitted')->exists()) {
            throw ValidationException::withMessages([
                'participant' => 'Participants with submitted evaluations cannot be removed.',
            ]);
        }

        DB::transaction(function () use ($participant, $actor): void {
            $this->auditLogger->log($participant, 'interview_participant_removed', [
                'interview_round_id' => $participant->interview_round_id,
            ], $actor);
            $participant->delete();
        });
    }

    protected function createParticipant(
        InterviewRound $round,
        array $data,
        User $actor,
        bool $audit = true,
    ): InterviewParticipant {
        $this->validateParticipantData($data, (int) $round->organization_id);

        $participant = InterviewParticipant::query()->create([
            'organization_id' => $round->organization_id,
            'interview_round_id' => $round->id,
            'participant_type' => $data['participant_type'],
            'employee_id' => $data['employee_id'] ?? null,
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'role' => $data['role'] ?? 'panel_member',
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        if ($audit) {
            $this->auditLogger->log($participant, 'interview_participant_assigned', [
                'interview_round_id' => $round->id,
                'participant_type' => $participant->participant_type,
            ], $actor);
        }

        return $participant;
    }

    protected function validateParticipantData(array $data, int $organizationId): void
    {
        $type = $data['participant_type'] ?? null;

        if (! in_array($type, array_keys(config('hrms.recruitment.participant_types', [])), true)) {
            throw ValidationException::withMessages([
                'participant_type' => 'Invalid participant type.',
            ]);
        }

        if ($type === 'internal') {
            if (empty($data['employee_id'])) {
                throw ValidationException::withMessages([
                    'employee_id' => 'Internal participants require an employee.',
                ]);
            }

            $exists = Employee::query()
                ->where('organization_id', $organizationId)
                ->where('id', $data['employee_id'])
                ->exists();

            if (! $exists) {
                throw ValidationException::withMessages([
                    'employee_id' => 'Selected employee does not exist in this organization.',
                ]);
            }
        }

        if ($type === 'external') {
            if (empty($data['name']) || empty($data['email'])) {
                throw ValidationException::withMessages([
                    'name' => 'External participants require a name and email.',
                ]);
            }
        }
    }

    protected function notifyParticipantAssigned(InterviewRound $round, InterviewParticipant $participant): void
    {
        $userId = $participant->employee?->user_id;

        if (! $userId) {
            return;
        }

        $candidateName = $round->jobApplication?->candidate?->fullName() ?? 'a candidate';

        try {
            $this->notificationService->send(
                (int) $round->organization_id,
                (int) $userId,
                'Interview assigned',
                sprintf('You have been assigned to interview %s.', $candidateName),
                '/hrms/recruitment/interview-rounds/'.$round->id,
            );
        } catch (ValidationException) {
            // Skip when recipient is not an organization member.
        }
    }
}
