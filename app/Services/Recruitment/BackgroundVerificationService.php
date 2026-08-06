<?php

namespace App\Services\Recruitment;

use App\Contracts\BackgroundVerificationProviderInterface;
use App\Models\HiringDecision;
use App\Models\Organization;
use App\Models\RecruitmentBackgroundVerification;
use App\Models\RecruitmentProvider;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Services\Recruitment\Providers\RecruitmentProviderRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class BackgroundVerificationService
{
    public function __construct(
        protected RecruitmentProviderRegistry $registry,
        protected RecruitmentProviderService $providers,
        protected AuditLogger $auditLogger,
        protected NotificationService $notificationService,
    ) {}

    public function submit(
        HiringDecision $decision,
        User $actor,
        ?string $providerSlug = null,
    ): RecruitmentBackgroundVerification {
        if (! $this->isHiringApproved($decision)) {
            throw ValidationException::withMessages([
                'hiring_decision_id' => 'Background verification starts only after hiring approval.',
            ]);
        }

        $organization = Organization::query()->findOrFail($decision->organization_id);
        $slug = $providerSlug ?? 'placeholder_bgv';
        $provider = $this->providers->findProvider($organization, $slug)
            ?? $this->providers->connect($organization, $slug, $actor);

        $adapter = $this->registry->resolve($provider->slug);
        if (! $adapter instanceof BackgroundVerificationProviderInterface) {
            throw ValidationException::withMessages([
                'provider' => 'Provider does not support background verification.',
            ]);
        }

        $decision->loadMissing('jobApplication.candidate');
        $candidateId = $decision->jobApplication?->candidate_id;

        if (! $candidateId) {
            throw ValidationException::withMessages([
                'hiring_decision_id' => 'Hiring decision must be linked to a candidate application.',
            ]);
        }

        return DB::transaction(function () use ($decision, $provider, $adapter, $actor, $organization, $candidateId) {
            $existing = RecruitmentBackgroundVerification::query()
                ->where('hiring_decision_id', $decision->id)
                ->where('recruitment_provider_id', $provider->id)
                ->first();

            if ($existing && ! in_array($existing->status, ['failed', 'cancelled'], true)) {
                return $existing;
            }

            $verification = $existing ?? new RecruitmentBackgroundVerification([
                'organization_id' => $organization->id,
                'recruitment_provider_id' => $provider->id,
                'candidate_id' => $candidateId,
                'hiring_decision_id' => $decision->id,
                'requested_by' => $actor->id,
            ]);

            $result = $adapter->submitVerification($provider, [
                'candidate_id' => $candidateId,
                'hiring_decision_id' => $decision->id,
                'candidate_name' => $decision->jobApplication?->candidate?->fullName(),
            ]);

            if (! ($result['ok'] ?? false)) {
                throw ValidationException::withMessages([
                    'provider' => $result['message'] ?? 'Verification submit failed.',
                ]);
            }

            $verification->fill([
                'external_verification_id' => $result['external_verification_id'] ?? null,
                'status' => $result['status'] ?? 'pending',
                'last_error' => null,
                'requested_by' => $actor->id,
            ]);
            $verification->save();

            $this->auditLogger->log($verification, 'recruitment_background_verification_submitted', [
                'provider' => $provider->slug,
                'hiring_decision_id' => $decision->id,
                'status' => $verification->status,
            ], $actor);

            $this->notifyStatus($verification, $actor, 'Background verification submitted');

            return $verification->fresh();
        });
    }

    public function refreshStatus(RecruitmentBackgroundVerification $verification, ?User $actor = null): RecruitmentBackgroundVerification
    {
        $provider = $verification->provider;
        if (! $provider || ! $verification->external_verification_id) {
            return $verification;
        }

        $adapter = $this->registry->resolve($provider->slug);
        if (! $adapter instanceof BackgroundVerificationProviderInterface) {
            return $verification;
        }

        try {
            $result = $adapter->checkStatus($provider, (string) $verification->external_verification_id);
            $status = $result['status'] ?? $verification->status;
            $this->assertValidStatus($status);

            $verification->update([
                'status' => $status,
                'result' => array_merge($verification->result ?? [], $result['metadata'] ?? []),
                'last_error' => ($result['ok'] ?? false) ? null : ($result['message'] ?? null),
                'completed_at' => in_array($status, ['completed', 'failed', 'cancelled'], true) ? now() : $verification->completed_at,
            ]);

            $this->auditLogger->log($verification, 'recruitment_background_verification_status', [
                'status' => $verification->status,
            ], $actor);

            if ($actor) {
                $this->notifyStatus($verification, $actor, 'Background verification status: '.$verification->statusLabel());
            }
        } catch (Throwable $e) {
            $verification->update(['last_error' => $e->getMessage()]);
        }

        return $verification->fresh();
    }

    public function uploadDocument(
        RecruitmentBackgroundVerification $verification,
        array $document,
        User $actor,
    ): RecruitmentBackgroundVerification {
        $provider = $verification->provider;
        if (! $provider || ! $verification->external_verification_id) {
            throw ValidationException::withMessages([
                'provider' => 'Verification is not linked to a provider.',
            ]);
        }

        $adapter = $this->registry->resolve($provider->slug);
        if (! $adapter instanceof BackgroundVerificationProviderInterface) {
            throw ValidationException::withMessages([
                'provider' => 'Provider does not support document upload.',
            ]);
        }

        $result = $adapter->uploadDocument($provider, (string) $verification->external_verification_id, $document);
        if (! ($result['ok'] ?? false)) {
            throw ValidationException::withMessages([
                'document' => $result['message'] ?? 'Document upload failed.',
            ]);
        }

        $documents = $verification->documents ?? [];
        $documents[] = [
            'document_id' => $result['document_id'] ?? null,
            'filename' => $document['filename'] ?? null,
            'uploaded_at' => now()->toIso8601String(),
        ];
        $verification->update(['documents' => $documents]);

        $this->auditLogger->log($verification, 'recruitment_background_verification_document_uploaded', [
            'document_id' => $result['document_id'] ?? null,
        ], $actor);

        return $verification->fresh();
    }

    public function cancel(RecruitmentBackgroundVerification $verification, User $actor): RecruitmentBackgroundVerification
    {
        $verification->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);

        $this->auditLogger->log($verification, 'recruitment_background_verification_cancelled', [], $actor);
        $this->notifyStatus($verification, $actor, 'Background verification cancelled');

        return $verification->fresh();
    }

    protected function isHiringApproved(HiringDecision $decision): bool
    {
        return $decision->recommendation === 'hire';
    }

    protected function assertValidStatus(string $status): void
    {
        $allowed = config('recruitment.background_verification.statuses', []);
        if (! in_array($status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "Invalid background verification status [{$status}].",
            ]);
        }
    }

    protected function notifyStatus(RecruitmentBackgroundVerification $verification, User $actor, string $title): void
    {
        try {
            $this->notificationService->send(
                $verification->organization_id,
                $actor->id,
                $title,
                'Candidate #'.$verification->candidate_id.' — '.$verification->statusLabel(),
                '/hrms/recruitment/integrations/background-verification',
            );
        } catch (Throwable) {
        }
    }
}
