<?php

namespace App\Services\Recruitment;

use App\Events\CandidateLoggedIn;
use App\Events\CandidateRegistered;
use App\Models\Candidate;
use App\Models\CandidateAccount;
use App\Models\Organization;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CandidateAccountService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function register(Organization $organization, array $data): CandidateAccount
    {
        $this->assertUniqueEmail($data['email'], $organization->id);

        return DB::transaction(function () use ($organization, $data): CandidateAccount {
            $candidate = Candidate::query()->create([
                'organization_id' => $organization->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => strtolower($data['email']),
                'phone' => $data['phone'] ?? null,
                'source' => 'careers_portal',
            ]);

            $account = CandidateAccount::query()->create([
                'organization_id' => $organization->id,
                'candidate_id' => $candidate->id,
                'email' => strtolower($data['email']),
                'password' => $data['password'],
            ]);

            $this->auditLogger->log($account, 'candidate_account_created', [
                'email' => $account->email,
                'candidate_id' => $candidate->id,
            ]);

            event(CandidateRegistered::forModel($account));

            return $account->load('candidate');
        });
    }

    public function recordLogin(CandidateAccount $account): void
    {
        $account->update(['last_login_at' => now()]);
        event(CandidateLoggedIn::forModel($account));
    }

    public function sendPasswordResetLink(Organization $organization, string $email): void
    {
        $account = CandidateAccount::query()
            ->where('organization_id', $organization->id)
            ->whereRaw('LOWER(email) = ?', [strtolower($email)])
            ->first();

        if (! $account) {
            return;
        }

        $token = Str::random(64);

        DB::table('candidate_password_reset_tokens')->updateOrInsert(
            [
                'organization_id' => $organization->id,
                'email' => $account->email,
            ],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ],
        );

        session()->flash('password_reset_token', $token);
    }

    public function resetPassword(Organization $organization, array $credentials): void
    {
        $email = strtolower($credentials['email']);
        $record = DB::table('candidate_password_reset_tokens')
            ->where('organization_id', $organization->id)
            ->where('email', $email)
            ->first();

        if (! $record || ! Hash::check($credentials['token'], $record->token)) {
            throw ValidationException::withMessages([
                'email' => 'This password reset token is invalid.',
            ]);
        }

        $createdAt = $record->created_at ? strtotime((string) $record->created_at) : 0;
        if ($createdAt < now()->subMinutes(60)->getTimestamp()) {
            throw ValidationException::withMessages([
                'email' => 'This password reset token has expired.',
            ]);
        }

        $account = CandidateAccount::query()
            ->where('organization_id', $organization->id)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->firstOrFail();

        $account->forceFill([
            'password' => $credentials['password'],
            'remember_token' => Str::random(60),
        ])->save();

        DB::table('candidate_password_reset_tokens')
            ->where('organization_id', $organization->id)
            ->where('email', $email)
            ->delete();
    }

    protected function assertUniqueEmail(string $email, int $organizationId): void
    {
        $exists = CandidateAccount::query()
            ->where('organization_id', $organizationId)
            ->whereRaw('LOWER(email) = ?', [strtolower($email)])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'email' => 'An account with this email already exists.',
            ]);
        }

        $candidateExists = Candidate::query()
            ->where('organization_id', $organizationId)
            ->whereRaw('LOWER(email) = ?', [strtolower($email)])
            ->exists();

        if ($candidateExists) {
            throw ValidationException::withMessages([
                'email' => 'This email is already associated with a candidate profile.',
            ]);
        }
    }
}
