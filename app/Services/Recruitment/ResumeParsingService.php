<?php

namespace App\Services\Recruitment;

use App\Contracts\ResumeParsingProviderInterface;
use App\Models\Candidate;
use App\Models\CandidateResume;
use App\Models\Organization;
use App\Models\RecruitmentProvider;
use App\Models\RecruitmentResumeParseRequest;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Services\Recruitment\Providers\RecruitmentProviderRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class ResumeParsingService
{
    public function __construct(
        protected RecruitmentProviderRegistry $registry,
        protected RecruitmentProviderService $providers,
        protected AuditLogger $auditLogger,
        protected NotificationService $notificationService,
        protected CandidateService $candidates,
    ) {}

    public function requestParse(
        Organization $organization,
        array $document,
        ?Candidate $candidate,
        ?CandidateResume $resume,
        User $actor,
        ?string $providerSlug = null,
    ): RecruitmentResumeParseRequest {
        $slug = $providerSlug ?? 'internal_resume_parser';
        $provider = $this->providers->findProvider($organization, $slug)
            ?? $this->providers->connect($organization, $slug, $actor);

        $adapter = $this->registry->resolve($provider->slug);
        if (! $adapter instanceof ResumeParsingProviderInterface) {
            throw ValidationException::withMessages([
                'provider' => 'Provider does not support resume parsing.',
            ]);
        }

        $request = RecruitmentResumeParseRequest::query()->create([
            'organization_id' => $organization->id,
            'recruitment_provider_id' => $provider->id,
            'candidate_id' => $candidate?->id,
            'candidate_resume_id' => $resume?->id,
            'status' => 'processing',
            'requested_by' => $actor->id,
        ]);

        try {
            $result = $adapter->parseResume($provider, $document);

            $request->update([
                'status' => ($result['ok'] ?? false) ? 'completed' : 'failed',
                'parsed_data' => $result,
                'last_error' => ($result['ok'] ?? false) ? null : ($result['message'] ?? 'Parse failed'),
            ]);

            $this->auditLogger->log($request, 'recruitment_resume_parse_requested', [
                'provider' => $provider->slug,
                'candidate_id' => $candidate?->id,
                'status' => $request->status,
            ], $actor);

            $this->notificationService->send(
                $organization->id,
                $actor->id,
                'Resume parsing completed',
                ($result['ok'] ?? false)
                    ? 'Resume parsing finished successfully.'
                    : 'Resume parsing failed: '.($result['message'] ?? 'Unknown error'),
                '/hrms/recruitment/integrations/resume-parsing',
            );
        } catch (Throwable $e) {
            $request->update([
                'status' => 'failed',
                'last_error' => $e->getMessage(),
            ]);
        }

        return $request->fresh();
    }

    /**
     * Apply parsed data to a candidate only when overwrite is confirmed
     * (or candidate fields are empty).
     */
    public function applyParsedData(
        RecruitmentResumeParseRequest $request,
        Candidate $candidate,
        User $actor,
        bool $overwriteConfirmed = false,
    ): Candidate {
        if ($request->status !== 'completed') {
            throw ValidationException::withMessages([
                'status' => 'Only completed parse requests can be applied.',
            ]);
        }

        $parsed = $request->parsed_data ?? [];
        $contact = $parsed['contact'] ?? [];

        $hasManualData = filled($candidate->email)
            || filled($candidate->phone)
            || filled($candidate->first_name);

        if ($hasManualData && ! $overwriteConfirmed) {
            throw ValidationException::withMessages([
                'overwrite' => 'Resume parsing never overwrites manually edited data without confirmation.',
            ]);
        }

        return DB::transaction(function () use ($request, $candidate, $actor, $contact, $overwriteConfirmed) {
            $data = [];
            if ($overwriteConfirmed || blank($candidate->email)) {
                if (! empty($contact['email'])) {
                    $data['email'] = $contact['email'];
                }
            }
            if ($overwriteConfirmed || blank($candidate->phone)) {
                if (! empty($contact['phone'])) {
                    $data['phone'] = $contact['phone'];
                }
            }

            if ($data !== []) {
                $candidate = $this->candidates->updateCandidate($candidate, $data, $actor);
            }

            $request->update([
                'candidate_id' => $candidate->id,
                'applied_to_candidate' => true,
                'overwrite_confirmed' => $overwriteConfirmed,
            ]);

            $this->auditLogger->log($request, 'recruitment_resume_parse_applied', [
                'candidate_id' => $candidate->id,
                'overwrite_confirmed' => $overwriteConfirmed,
            ], $actor);

            return $candidate;
        });
    }
}
