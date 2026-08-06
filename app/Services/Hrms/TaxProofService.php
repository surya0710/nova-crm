<?php

namespace App\Services\Hrms;

use App\Events\TaxProofUploaded;
use App\Events\TaxProofVerified;
use App\Models\TaxDeclaration;
use App\Models\TaxProof;
use App\Models\TaxProofAudit;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TaxProofService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function upload(
        TaxDeclaration $declaration,
        array $data,
        ?UploadedFile $file,
        ?User $actor = null,
    ): TaxProof {
        return DB::transaction(function () use ($declaration, $data, $file, $actor): TaxProof {
            $declaration->refresh()->load('employee');

            if (! in_array($declaration->status, [
                TaxDeclaration::STATUS_SUBMITTED,
                TaxDeclaration::STATUS_VERIFIED,
            ], true)) {
                throw ValidationException::withMessages([
                    'declaration' => 'Proofs can only be uploaded for submitted or verified declarations.',
                ]);
            }

            $proof = TaxProof::query()->create([
                'organization_id' => $declaration->organization_id,
                'tax_declaration_id' => $declaration->id,
                'tax_declaration_item_id' => $data['tax_declaration_item_id'] ?? null,
                'employee_id' => $declaration->employee_id,
                'proof_number' => $this->nextProofNumber(),
                'category' => (string) ($data['category'] ?? 'other'),
                'title' => (string) ($data['title'] ?? 'Tax Proof'),
                'claimed_amount' => round((float) ($data['claimed_amount'] ?? 0), 2),
                'status' => TaxProof::STATUS_UPLOADED,
                'uploaded_by' => $actor?->id,
            ]);

            if ($file !== null) {
                $stored = $this->storeFile($declaration->organization_id, $file);
                $proof->update($stored);
            }

            $this->writeAudit($proof, 'uploaded', null, TaxProof::STATUS_UPLOADED, null, null, $actor);

            $this->auditLogger->log($proof, 'tax_proof_uploaded', [
                'declaration_id' => $declaration->id,
                'category' => $proof->category,
                'claimed_amount' => $proof->claimed_amount,
            ], $actor);

            event(TaxProofUploaded::forModel($proof->fresh(), [
                'employee_id' => $declaration->employee_id,
                'declaration_id' => $declaration->id,
                'actor_id' => $actor?->id,
            ]));

            return $proof->fresh(['declaration', 'item']);
        });
    }

    public function verify(
        TaxProof $proof,
        float $approvedAmount,
        ?string $comments = null,
        ?User $actor = null,
    ): TaxProof {
        return DB::transaction(function () use ($proof, $approvedAmount, $comments, $actor): TaxProof {
            $proof->refresh();

            if (! in_array($proof->status, [TaxProof::STATUS_UPLOADED, TaxProof::STATUS_PARTIAL], true)) {
                throw ValidationException::withMessages([
                    'proof' => 'Only uploaded or partially approved proofs can be verified.',
                ]);
            }

            $approvedAmount = round($approvedAmount, 2);
            $claimed = (float) $proof->claimed_amount;
            $fromStatus = $proof->status;
            $toStatus = $approvedAmount >= $claimed
                ? TaxProof::STATUS_VERIFIED
                : TaxProof::STATUS_PARTIAL;

            $proof->update([
                'status' => $toStatus,
                'approved_amount' => $approvedAmount,
                'comments' => $comments,
                'verified_by' => $actor?->id,
                'verified_at' => now(),
            ]);

            $this->writeAudit($proof, 'verified', $fromStatus, $toStatus, $approvedAmount, $comments, $actor);

            $this->auditLogger->log($proof, 'tax_proof_verified', [
                'approved_amount' => $approvedAmount,
                'status' => $toStatus,
            ], $actor);

            event(TaxProofVerified::forModel($proof->fresh(), [
                'employee_id' => $proof->employee_id,
                'approved_amount' => $approvedAmount,
                'status' => $toStatus,
                'actor_id' => $actor?->id,
            ]));

            return $proof->fresh(['declaration', 'item']);
        });
    }

    public function reject(TaxProof $proof, string $comments, ?User $actor = null): TaxProof
    {
        return DB::transaction(function () use ($proof, $comments, $actor): TaxProof {
            $proof->refresh();

            if ($proof->status === TaxProof::STATUS_REJECTED) {
                throw ValidationException::withMessages([
                    'proof' => 'This proof has already been rejected.',
                ]);
            }

            $fromStatus = $proof->status;

            $proof->update([
                'status' => TaxProof::STATUS_REJECTED,
                'approved_amount' => 0,
                'comments' => $comments,
                'verified_by' => $actor?->id,
                'verified_at' => now(),
            ]);

            $this->writeAudit($proof, 'rejected', $fromStatus, TaxProof::STATUS_REJECTED, 0, $comments, $actor);

            $this->auditLogger->log($proof, 'tax_proof_rejected', [
                'comments' => $comments,
            ], $actor);

            return $proof->fresh(['declaration', 'item']);
        });
    }

    /**
     * @return array{file_path: string, original_filename: string, mime_type: string, file_size: int}
     */
    protected function storeFile(int $organizationId, UploadedFile $file): array
    {
        $disk = config('hrms.payslips.disk', 'local');
        $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
        $path = 'hrms-tax-proofs/'.$organizationId.'/'.$filename;

        Storage::disk($disk)->putFileAs(
            'hrms-tax-proofs/'.$organizationId,
            $file,
            $filename
        );

        return [
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType() ?? 'application/octet-stream',
            'file_size' => $file->getSize() ?: 0,
        ];
    }

    protected function writeAudit(
        TaxProof $proof,
        string $action,
        ?string $fromStatus,
        string $toStatus,
        ?float $approvedAmount,
        ?string $comments,
        ?User $actor,
    ): void {
        TaxProofAudit::query()->create([
            'organization_id' => $proof->organization_id,
            'tax_proof_id' => $proof->id,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'approved_amount' => $approvedAmount,
            'comments' => $comments,
            'actor_id' => $actor?->id,
        ]);
    }

    protected function nextProofNumber(): string
    {
        $organizationId = $this->tenantContext->id();
        $seq = TaxProof::query()->where('organization_id', $organizationId)->count() + 1;

        return sprintf('TPR-%s-%04d', now()->format('Ymd'), $seq);
    }
}
