<?php

namespace App\Services\Hrms;

use App\Events\FeedbackCampaignCreated;
use App\Events\FeedbackClosed;
use App\Events\FeedbackRequestSent;
use App\Events\FeedbackStarted;
use App\Events\FeedbackSubmitted;
use App\Models\Employee;
use App\Models\FeedbackCampaign;
use App\Models\FeedbackParticipant;
use App\Models\FeedbackQuestion;
use App\Models\FeedbackRequest;
use App\Models\FeedbackResponse;
use App\Models\FeedbackTemplate;
use App\Models\PerformanceConfiguration;
use App\Models\PerformanceCycle;
use App\Models\PerformanceReview;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeedbackService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
    ) {}

    // -------------------------------------------------------------------------
    // Campaign lifecycle
    // -------------------------------------------------------------------------

    public function createCampaign(array $data, User $actor): FeedbackCampaign
    {
        return DB::transaction(function () use ($data, $actor): FeedbackCampaign {
            $cycle = $this->assertOrgCycle((int) $data['performance_cycle_id']);
            $template = $this->assertOrgFeedbackTemplate((int) $data['feedback_template_id']);
            $status = $data['status'] ?? 'draft';
            $this->assertConfigKey('feedback_campaign_statuses', $status);

            $isAnonymous = $this->resolveAnonymousFlag($data['is_anonymous'] ?? null);

            $campaign = FeedbackCampaign::query()->create([
                'performance_cycle_id' => $cycle->id,
                'feedback_template_id' => $template->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'start_date' => $data['start_date'],
                'due_date' => $data['due_date'],
                'is_anonymous' => $isAnonymous,
                'status' => $status,
                'created_by' => $actor->id,
            ]);

            $this->auditLogger->log($campaign, 'feedback_campaign_created', [
                'cycle_id' => $cycle->id,
                'template_id' => $template->id,
                'is_anonymous' => $isAnonymous,
            ], $actor);

            event(FeedbackCampaignCreated::forModel($campaign, [
                'actor_id' => $actor->id,
            ]));

            return $campaign->fresh(['cycle', 'template']);
        });
    }

    public function updateCampaign(FeedbackCampaign $campaign, array $data, User $actor): FeedbackCampaign
    {
        return DB::transaction(function () use ($campaign, $data, $actor): FeedbackCampaign {
            $campaign->refresh();

            if (! $campaign->isEditable()) {
                throw ValidationException::withMessages([
                    'campaign' => 'Only draft or scheduled campaigns can be updated.',
                ]);
            }

            if (isset($data['performance_cycle_id'])) {
                $this->assertOrgCycle((int) $data['performance_cycle_id']);
            }
            if (isset($data['feedback_template_id'])) {
                $this->assertOrgFeedbackTemplate((int) $data['feedback_template_id']);
            }
            if (isset($data['status'])) {
                $this->assertConfigKey('feedback_campaign_statuses', $data['status']);
            }

            $updates = collect($data)->only([
                'performance_cycle_id', 'feedback_template_id', 'name', 'description',
                'start_date', 'due_date', 'status',
            ])->filter(fn ($v) => $v !== null)->all();

            if (array_key_exists('is_anonymous', $data)) {
                $updates['is_anonymous'] = $this->resolveAnonymousFlag($data['is_anonymous']);
            }

            $campaign->update($updates);

            $this->auditLogger->log($campaign, 'feedback_campaign_updated', [], $actor);

            return $campaign->fresh(['cycle', 'template']);
        });
    }

    public function activateCampaign(FeedbackCampaign $campaign, User $actor): FeedbackCampaign
    {
        return DB::transaction(function () use ($campaign, $actor): FeedbackCampaign {
            $campaign->refresh();

            if (! in_array($campaign->status, config('hrms.feedback.activatable_campaign_statuses', ['draft', 'scheduled']), true)) {
                throw ValidationException::withMessages([
                    'campaign' => 'Only draft or scheduled campaigns can be activated.',
                ]);
            }

            $campaign->update(['status' => 'active']);

            $this->auditLogger->log($campaign, 'feedback_campaign_activated', [], $actor);

            return $campaign->fresh();
        });
    }

    public function closeCampaign(FeedbackCampaign $campaign, User $actor): FeedbackCampaign
    {
        return DB::transaction(function () use ($campaign, $actor): FeedbackCampaign {
            $campaign->refresh();

            if (! in_array($campaign->status, config('hrms.feedback.closable_campaign_statuses', ['active']), true)) {
                throw ValidationException::withMessages([
                    'campaign' => 'Only active campaigns can be closed.',
                ]);
            }

            $campaign->update(['status' => 'closed']);

            FeedbackRequest::query()
                ->where('feedback_campaign_id', $campaign->id)
                ->whereIn('status', ['pending', 'started'])
                ->update(['status' => 'expired']);

            $this->generateSummary($campaign, $actor);

            $this->auditLogger->log($campaign, 'feedback_campaign_closed', [], $actor);

            event(FeedbackClosed::forModel($campaign, [
                'actor_id' => $actor->id,
            ]));

            return $campaign->fresh();
        });
    }

    public function archiveCampaign(FeedbackCampaign $campaign, User $actor): FeedbackCampaign
    {
        return DB::transaction(function () use ($campaign, $actor): FeedbackCampaign {
            $campaign->refresh();

            if ($campaign->status !== 'closed') {
                throw ValidationException::withMessages([
                    'campaign' => 'Only closed campaigns can be archived.',
                ]);
            }

            $campaign->update(['status' => 'archived']);
            $this->auditLogger->log($campaign, 'feedback_campaign_archived', [], $actor);

            return $campaign->fresh();
        });
    }

    public function resolveActiveCampaign(?int $cycleId = null): ?FeedbackCampaign
    {
        $query = FeedbackCampaign::query()->where('status', 'active');

        if ($cycleId) {
            $query->where('performance_cycle_id', $cycleId);
        }

        return $query->orderByDesc('start_date')->first();
    }

    // -------------------------------------------------------------------------
    // Templates
    // -------------------------------------------------------------------------

    public function createTemplate(array $data, User $actor): FeedbackTemplate
    {
        return DB::transaction(function () use ($data, $actor): FeedbackTemplate {
            $template = FeedbackTemplate::query()->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            if (! empty($data['questions'])) {
                $this->syncTemplateQuestions($template, $data['questions']);
            }

            $this->auditLogger->log($template, 'feedback_template_created', [], $actor);

            return $template->fresh('questions');
        });
    }

    public function updateTemplate(FeedbackTemplate $template, array $data, User $actor): FeedbackTemplate
    {
        return DB::transaction(function () use ($template, $data, $actor): FeedbackTemplate {
            $template->update(collect($data)->only(['name', 'description', 'is_active'])->filter(fn ($v) => $v !== null)->all());

            if (array_key_exists('questions', $data)) {
                $this->syncTemplateQuestions($template, $data['questions']);
            }

            $this->auditLogger->log($template, 'feedback_template_updated', [], $actor);

            return $template->fresh('questions');
        });
    }

    protected function syncTemplateQuestions(FeedbackTemplate $template, array $questions): void
    {
        $existingIds = [];

        foreach ($questions as $index => $questionData) {
            $this->assertConfigKey('feedback_question_types', $questionData['question_type']);

            $attrs = [
                'question_type' => $questionData['question_type'],
                'competency_id' => $questionData['competency_id'] ?? null,
                'question_text' => $questionData['question_text'],
                'help_text' => $questionData['help_text'] ?? null,
                'scale_min' => $questionData['scale_min'] ?? null,
                'scale_max' => $questionData['scale_max'] ?? null,
                'sort_order' => $questionData['sort_order'] ?? $index,
                'is_required' => $questionData['is_required'] ?? true,
            ];

            if (! empty($questionData['id'])) {
                $question = FeedbackQuestion::query()
                    ->where('feedback_template_id', $template->id)
                    ->where('id', $questionData['id'])
                    ->firstOrFail();
                $question->update($attrs);
                $existingIds[] = $question->id;
            } else {
                $question = $template->questions()->create($attrs);
                $existingIds[] = $question->id;
            }
        }

        $template->questions()->whereNotIn('id', $existingIds)->delete();
    }

    // -------------------------------------------------------------------------
    // Participants
    // -------------------------------------------------------------------------

    public function addParticipant(FeedbackCampaign $campaign, array $data, User $actor): FeedbackParticipant
    {
        return DB::transaction(function () use ($campaign, $data, $actor): FeedbackParticipant {
            $campaign->refresh();

            if ($campaign->isClosed()) {
                throw ValidationException::withMessages([
                    'campaign' => 'Cannot add participants to a closed or archived campaign.',
                ]);
            }

            $this->assertConfigKey('feedback_participant_types', $data['participant_type']);
            $subject = $this->assertOrgEmployee((int) $data['subject_employee_id']);

            $participantEmployeeId = isset($data['participant_employee_id'])
                ? $this->assertOrgEmployee((int) $data['participant_employee_id'])->id
                : null;

            $reviewId = isset($data['performance_review_id'])
                ? $this->assertOrgReview((int) $data['performance_review_id'])->id
                : null;

            if ($data['participant_type'] === 'external') {
                if (empty($data['external_name']) || empty($data['external_email'])) {
                    throw ValidationException::withMessages([
                        'external_email' => 'External participants require a name and email.',
                    ]);
                }
            } elseif (! $participantEmployeeId) {
                throw ValidationException::withMessages([
                    'participant_employee_id' => 'Internal participants require an employee.',
                ]);
            }

            $participant = FeedbackParticipant::query()->create([
                'feedback_campaign_id' => $campaign->id,
                'performance_review_id' => $reviewId,
                'subject_employee_id' => $subject->id,
                'participant_employee_id' => $participantEmployeeId,
                'external_name' => $data['external_name'] ?? null,
                'external_email' => $data['external_email'] ?? null,
                'participant_type' => $data['participant_type'],
                'status' => 'active',
            ]);

            $this->auditLogger->log($participant, 'feedback_participant_assigned', [
                'campaign_id' => $campaign->id,
                'subject_employee_id' => $subject->id,
                'participant_type' => $data['participant_type'],
            ], $actor);

            return $participant->fresh(['subjectEmployee', 'participantEmployee']);
        });
    }

    public function removeParticipant(FeedbackParticipant $participant, User $actor): FeedbackParticipant
    {
        return DB::transaction(function () use ($participant, $actor): FeedbackParticipant {
            $participant->update(['status' => 'removed']);

            $request = $participant->request;
            if ($request && $request->isSubmittable()) {
                $request->update(['status' => 'cancelled']);
            }

            $this->auditLogger->log($participant, 'feedback_participant_removed', [
                'campaign_id' => $participant->feedback_campaign_id,
            ], $actor);

            return $participant->fresh();
        });
    }

    // -------------------------------------------------------------------------
    // Request generation
    // -------------------------------------------------------------------------

    public function generateRequests(FeedbackCampaign $campaign, User $actor): Collection
    {
        return DB::transaction(function () use ($campaign, $actor): Collection {
            $campaign->refresh();

            if (! $campaign->isActive() && $campaign->status !== 'scheduled') {
                throw ValidationException::withMessages([
                    'campaign' => 'Requests can only be generated for scheduled or active campaigns.',
                ]);
            }

            $participants = FeedbackParticipant::query()
                ->where('feedback_campaign_id', $campaign->id)
                ->where('status', 'active')
                ->whereDoesntHave('request')
                ->get();

            $requests = collect();

            foreach ($participants as $participant) {
                $request = FeedbackRequest::query()->create([
                    'feedback_campaign_id' => $campaign->id,
                    'feedback_participant_id' => $participant->id,
                    'performance_review_id' => $participant->performance_review_id,
                    'subject_employee_id' => $participant->subject_employee_id,
                    'participant_employee_id' => $participant->participant_employee_id,
                    'participant_type' => $participant->participant_type,
                    'due_date' => $campaign->due_date,
                    'status' => 'pending',
                    'is_anonymous' => $campaign->is_anonymous,
                ]);

                $this->auditLogger->log($request, 'feedback_request_generated', [
                    'campaign_id' => $campaign->id,
                    'participant_type' => $participant->participant_type,
                ], $actor);

                event(FeedbackRequestSent::forModel($request, [
                    'actor_id' => $actor->id,
                    'campaign_id' => $campaign->id,
                ]));

                $requests->push($request);
            }

            return $requests;
        });
    }

    // -------------------------------------------------------------------------
    // Feedback submission
    // -------------------------------------------------------------------------

    public function startFeedback(FeedbackRequest $request, User $actor): FeedbackRequest
    {
        return DB::transaction(function () use ($request, $actor): FeedbackRequest {
            $request->refresh();
            $this->assertRequestActor($request, $actor);

            if ($request->status !== 'pending') {
                if ($request->status === 'started') {
                    return $request;
                }

                throw ValidationException::withMessages([
                    'request' => 'This feedback request cannot be started.',
                ]);
            }

            $request->update([
                'status' => 'started',
                'started_at' => Carbon::now(),
            ]);

            $this->auditLogger->log($request, 'feedback_started', [], $actor);

            event(FeedbackStarted::forModel($request, [
                'actor_id' => $actor->id,
            ]));

            return $request->fresh(['campaign.template.questions']);
        });
    }

    public function submitFeedback(FeedbackRequest $request, array $responses, User $actor): FeedbackRequest
    {
        return DB::transaction(function () use ($request, $responses, $actor): FeedbackRequest {
            $request->refresh();
            $this->assertRequestActor($request, $actor);

            if (! $request->isSubmittable()) {
                throw ValidationException::withMessages([
                    'request' => 'This feedback request cannot be submitted.',
                ]);
            }

            $campaign = $request->campaign()->with('template.questions')->firstOrFail();
            $questions = $campaign->template->questions->keyBy('id');
            $reviewerEmployee = $this->resolveActorEmployee($actor);

            $this->validateResponses($questions, $responses);

            $now = Carbon::now();

            foreach ($responses as $responseData) {
                $questionId = (int) $responseData['feedback_question_id'];
                $question = $questions->get($questionId);

                if (! $question) {
                    throw ValidationException::withMessages([
                        'responses' => "Invalid question ID: {$questionId}",
                    ]);
                }

                FeedbackResponse::query()->updateOrCreate(
                    [
                        'feedback_request_id' => $request->id,
                        'feedback_question_id' => $questionId,
                    ],
                    [
                        'rating' => $responseData['rating'] ?? null,
                        'text_response' => $responseData['text_response'] ?? null,
                        'reviewer_employee_id' => $reviewerEmployee?->id,
                        'submitted_at' => $now,
                    ]
                );
            }

            $request->update([
                'status' => 'submitted',
                'submitted_at' => $now,
                'started_at' => $request->started_at ?? $now,
            ]);

            $this->auditLogger->log($request, 'feedback_submitted', [
                'campaign_id' => $campaign->id,
                'is_anonymous' => $request->is_anonymous,
                'response_count' => count($responses),
            ], $actor);

            event(FeedbackSubmitted::forModel($request, [
                'actor_id' => $actor->id,
                'campaign_id' => $campaign->id,
            ]));

            return $request->fresh(['responses.question']);
        });
    }

    protected function validateResponses(Collection $questions, array $responses): void
    {
        $answeredIds = collect($responses)->pluck('feedback_question_id')->map(fn ($id) => (int) $id);

        foreach ($questions as $question) {
            if ($question->is_required && ! $answeredIds->contains($question->id)) {
                throw ValidationException::withMessages([
                    'responses' => "Required question not answered: {$question->question_text}",
                ]);
            }
        }

        foreach ($responses as $responseData) {
            $questionId = (int) ($responseData['feedback_question_id'] ?? 0);
            $question = $questions->get($questionId);

            if (! $question) {
                continue;
            }

            if ($question->isRatingQuestion()) {
                $rating = $responseData['rating'] ?? null;
                if ($rating === null || $rating === '') {
                    if ($question->is_required) {
                        throw ValidationException::withMessages([
                            'responses' => "Rating required for: {$question->question_text}",
                        ]);
                    }

                    continue;
                }

                $min = $question->scale_min ?? 1;
                $max = $question->scale_max ?? 5;

                if ((float) $rating < $min || (float) $rating > $max) {
                    throw ValidationException::withMessages([
                        'responses' => "Rating for \"{$question->question_text}\" must be between {$min} and {$max}.",
                    ]);
                }
            } elseif ($question->question_type === 'text' && $question->is_required) {
                if (empty(trim($responseData['text_response'] ?? ''))) {
                    throw ValidationException::withMessages([
                        'responses' => "Text response required for: {$question->question_text}",
                    ]);
                }
            }
        }
    }

    // -------------------------------------------------------------------------
    // Aggregation & Summary
    // -------------------------------------------------------------------------

    public function aggregateFeedback(FeedbackCampaign $campaign, ?int $subjectEmployeeId = null): array
    {
        $campaign->load('template.questions');

        $requestsQuery = FeedbackRequest::query()
            ->where('feedback_campaign_id', $campaign->id)
            ->where('status', 'submitted');

        if ($subjectEmployeeId) {
            $requestsQuery->where('subject_employee_id', $subjectEmployeeId);
        }

        $requestIds = $requestsQuery->pluck('id');

        $responses = FeedbackResponse::query()
            ->whereIn('feedback_request_id', $requestIds)
            ->with(['question', 'request'])
            ->get();

        $byCompetency = [];
        $byParticipantType = [];
        $allRatings = [];
        $textResponses = [];

        foreach ($responses as $response) {
            $question = $response->question;
            $request = $response->request;

            if ($response->rating !== null) {
                $allRatings[] = (float) $response->rating;

                $type = $request->participant_type;
                if (! isset($byParticipantType[$type])) {
                    $byParticipantType[$type] = ['ratings' => [], 'count' => 0];
                }
                $byParticipantType[$type]['ratings'][] = (float) $response->rating;
                $byParticipantType[$type]['count']++;

                if ($question->competency_id) {
                    $key = $question->competency_id;
                    if (! isset($byCompetency[$key])) {
                        $byCompetency[$key] = [
                            'competency_id' => $question->competency_id,
                            'question_text' => $question->question_text,
                            'ratings' => [],
                        ];
                    }
                    $byCompetency[$key]['ratings'][] = (float) $response->rating;
                }
            }

            if ($response->text_response) {
                $textResponses[] = $response->text_response;
            }
        }

        $competencyBreakdown = collect($byCompetency)->map(function (array $item) {
            return [
                'competency_id' => $item['competency_id'],
                'question_text' => $item['question_text'],
                'average_rating' => round(collect($item['ratings'])->avg(), 2),
                'response_count' => count($item['ratings']),
                'distribution' => $this->ratingDistribution($item['ratings']),
            ];
        })->values()->all();

        $participantTypeBreakdown = collect($byParticipantType)->map(function (array $item, string $type) {
            return [
                'participant_type' => $type,
                'average_rating' => round(collect($item['ratings'])->avg(), 2),
                'response_count' => $item['count'],
                'distribution' => $this->ratingDistribution($item['ratings']),
            ];
        })->values()->all();

        return [
            'overall_average' => $allRatings !== [] ? round(collect($allRatings)->avg(), 2) : null,
            'total_responses' => count($allRatings),
            'overall_distribution' => $this->ratingDistribution($allRatings),
            'by_competency' => $competencyBreakdown,
            'by_participant_type' => $participantTypeBreakdown,
            'text_responses' => $textResponses,
        ];
    }

    public function generateSummary(FeedbackCampaign $campaign, User $actor): array
    {
        $aggregation = $this->aggregateFeedback($campaign);

        $totalRequests = FeedbackRequest::query()
            ->where('feedback_campaign_id', $campaign->id)
            ->whereNotIn('status', ['cancelled'])
            ->count();

        $submittedRequests = FeedbackRequest::query()
            ->where('feedback_campaign_id', $campaign->id)
            ->where('status', 'submitted')
            ->count();

        $participationRate = $totalRequests > 0
            ? round(($submittedRequests / $totalRequests) * 100, 1)
            : 0;

        $textResponses = $aggregation['text_responses'] ?? [];
        $strengths = $this->extractThemedResponses($textResponses, 'strength');
        $improvements = $this->extractThemedResponses($textResponses, 'improvement');
        $themes = $this->extractCommonThemes($textResponses);

        $summary = [
            'strengths' => $strengths,
            'improvement_areas' => $improvements,
            'common_themes' => $themes,
            'competency_breakdown' => $aggregation['by_competency'],
            'participant_type_breakdown' => $aggregation['by_participant_type'],
            'overall_average' => $aggregation['overall_average'],
            'participation_rate' => $participationRate,
            'total_requests' => $totalRequests,
            'submitted_requests' => $submittedRequests,
            'generated_at' => Carbon::now()->toIso8601String(),
        ];

        $campaign->update([
            'summary' => $summary,
            'summary_generated_at' => Carbon::now(),
        ]);

        $this->auditLogger->log($campaign, 'feedback_summary_generated', [
            'participation_rate' => $participationRate,
        ], $actor);

        return $summary;
    }

    protected function ratingDistribution(array $ratings): array
    {
        $distribution = [];
        foreach ($ratings as $rating) {
            $key = (string) (int) round($rating);
            $distribution[$key] = ($distribution[$key] ?? 0) + 1;
        }
        ksort($distribution);

        return $distribution;
    }

    protected function extractThemedResponses(array $textResponses, string $theme): array
    {
        $keywords = $theme === 'strength'
            ? ['strong', 'excellent', 'great', 'good at', 'skilled', 'effective', 'leadership', 'collaborat']
            : ['improve', 'weak', 'needs', 'better', 'lack', 'should', 'develop', 'growth'];

        return collect($textResponses)
            ->filter(function (string $text) use ($keywords) {
                $lower = strtolower($text);

                return collect($keywords)->contains(fn ($kw) => str_contains($lower, $kw));
            })
            ->take(10)
            ->values()
            ->all();
    }

    protected function extractCommonThemes(array $textResponses): array
    {
        $wordCounts = [];

        foreach ($textResponses as $text) {
            $words = str_word_count(strtolower($text), 1);
            foreach ($words as $word) {
                if (strlen($word) < 4) {
                    continue;
                }
                $wordCounts[$word] = ($wordCounts[$word] ?? 0) + 1;
            }
        }

        arsort($wordCounts);

        return collect($wordCounts)
            ->take(10)
            ->map(fn ($count, $word) => ['theme' => $word, 'frequency' => $count])
            ->values()
            ->all();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function resolveAnonymousFlag(?bool $requested): bool
    {
        $config = PerformanceConfiguration::query()->first();

        if ($config?->feedback_anonymous_required) {
            return true;
        }

        if ($config && ! $config->feedback_anonymous_enabled && $requested === true) {
            throw ValidationException::withMessages([
                'is_anonymous' => 'Anonymous feedback is disabled for this organization.',
            ]);
        }

        return $requested ?? (bool) ($config?->feedback_anonymous_enabled ?? true);
    }

    protected function assertRequestActor(FeedbackRequest $request, User $actor): void
    {
        if ($actor->hasPermission('performance.feedback.manage', $request->organization)) {
            return;
        }

        $employee = $this->resolveActorEmployee($actor);

        if (! $employee || (int) $request->participant_employee_id !== (int) $employee->id) {
            throw ValidationException::withMessages([
                'request' => 'You are not authorized to act on this feedback request.',
            ]);
        }
    }

    protected function resolveActorEmployee(User $actor): ?Employee
    {
        $org = $this->tenantContext->get();

        if (! $org) {
            return null;
        }

        return Employee::query()
            ->where('organization_id', $org->id)
            ->where('user_id', $actor->id)
            ->first();
    }

    protected function assertConfigKey(string $configKey, string $value): void
    {
        if (! array_key_exists($value, config("hrms.{$configKey}", []))) {
            throw ValidationException::withMessages([
                $configKey => "Invalid value: {$value}",
            ]);
        }
    }

    protected function assertOrgCycle(int $id): PerformanceCycle
    {
        return PerformanceCycle::query()->findOrFail($id);
    }

    protected function assertOrgEmployee(int $id): Employee
    {
        return Employee::query()->findOrFail($id);
    }

    protected function assertOrgFeedbackTemplate(int $id): FeedbackTemplate
    {
        return FeedbackTemplate::query()->findOrFail($id);
    }

    protected function assertOrgReview(int $id): PerformanceReview
    {
        return PerformanceReview::query()->findOrFail($id);
    }
}
