<?php

namespace App\Services\Hrms;

use App\Events\TaxDeclarationApproved;
use App\Events\TaxDeclarationRejected;
use App\Events\TaxDeclarationSubmitted;
use App\Models\Employee;
use App\Models\TaxDeclaration;
use App\Models\TaxDeclarationItem;
use App\Models\TaxFinancialYear;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Services\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvestmentDeclarationService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
        protected NotificationService $notificationService,
    ) {}

    /**
     * @param  list<array{category: string, section?: string|null, label: string, declared_amount: float|int|string}>  $items
     */
    public function createDraft(Employee $employee, TaxFinancialYear $fy, array $items, ?User $actor = null): TaxDeclaration
    {
        return DB::transaction(function () use ($employee, $fy, $items, $actor): TaxDeclaration {
            $existing = TaxDeclaration::query()
                ->where('employee_id', $employee->id)
                ->where('tax_financial_year_id', $fy->id)
                ->whereIn('status', [TaxDeclaration::STATUS_DRAFT, TaxDeclaration::STATUS_REJECTED])
                ->first();

            if ($existing) {
                return $this->updateDraft($existing, $items, $actor);
            }

            $declaration = TaxDeclaration::query()->create([
                'organization_id' => $employee->organization_id,
                'employee_id' => $employee->id,
                'tax_financial_year_id' => $fy->id,
                'declaration_number' => $this->nextDeclarationNumber(),
                'status' => TaxDeclaration::STATUS_DRAFT,
                'declared_total' => 0,
                'approved_total' => 0,
            ]);

            $declaration->update([
                'declaration_number' => $this->formatDeclarationNumber($declaration),
            ]);

            $this->syncItems($declaration, $items);
            $declaration->refresh()->load('items');

            $this->auditLogger->log($declaration, 'tax_declaration_created', [
                'employee_id' => $employee->id,
                'financial_year_id' => $fy->id,
                'declared_total' => $declaration->declared_total,
            ], $actor);

            return $declaration;
        });
    }

    /**
     * @param  list<array{category: string, section?: string|null, label: string, declared_amount: float|int|string}>  $items
     */
    public function updateDraft(TaxDeclaration $declaration, array $items, ?User $actor = null): TaxDeclaration
    {
        return DB::transaction(function () use ($declaration, $items, $actor): TaxDeclaration {
            $declaration->refresh();

            if (! $declaration->isEditable()) {
                throw ValidationException::withMessages([
                    'declaration' => 'Only draft or rejected declarations can be updated.',
                ]);
            }

            $this->syncItems($declaration, $items);

            if ($declaration->status === TaxDeclaration::STATUS_REJECTED) {
                $declaration->update([
                    'status' => TaxDeclaration::STATUS_DRAFT,
                    'rejection_reason' => null,
                    'verified_at' => null,
                    'verified_by' => null,
                    'verifier_comments' => null,
                    'approved_total' => 0,
                ]);
            }

            $declaration->refresh()->load('items');

            $this->auditLogger->log($declaration, 'tax_declaration_updated', [
                'declared_total' => $declaration->declared_total,
                'item_count' => $declaration->items->count(),
            ], $actor);

            return $declaration;
        });
    }

    public function submit(TaxDeclaration $declaration, ?User $actor = null): TaxDeclaration
    {
        return DB::transaction(function () use ($declaration, $actor): TaxDeclaration {
            $declaration->refresh()->load(['employee', 'items']);

            if (! $declaration->canSubmit()) {
                throw ValidationException::withMessages([
                    'declaration' => 'This declaration cannot be submitted in its current status.',
                ]);
            }

            if ($declaration->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'At least one declaration item is required before submission.',
                ]);
            }

            $declaration->update([
                'status' => TaxDeclaration::STATUS_SUBMITTED,
                'submitted_at' => now(),
                'submitted_by' => $actor?->id,
                'rejection_reason' => null,
            ]);

            $this->auditLogger->log($declaration, 'tax_declaration_submitted', [
                'declared_total' => $declaration->declared_total,
            ], $actor);

            event(TaxDeclarationSubmitted::forModel($declaration->fresh(), [
                'employee_id' => $declaration->employee_id,
                'actor_id' => $actor?->id,
            ]));

            $this->notifyEmployee(
                $declaration,
                __('Investment declaration submitted'),
                __('Your tax investment declaration :number has been submitted for verification.', [
                    'number' => $declaration->declaration_number,
                ]),
                '/hrms/ess/tax/declarations/'.$declaration->id
            );

            return $declaration->fresh(['employee', 'items', 'financialYear']);
        });
    }

    public function verify(TaxDeclaration $declaration, ?User $actor = null, ?string $comments = null): TaxDeclaration
    {
        return DB::transaction(function () use ($declaration, $actor, $comments): TaxDeclaration {
            $declaration->refresh()->load('items');

            if (! $declaration->canVerify()) {
                throw ValidationException::withMessages([
                    'declaration' => 'Only submitted declarations can be verified.',
                ]);
            }

            $approvedTotal = 0.0;

            foreach ($declaration->items as $item) {
                $approved = $item->approved_amount ?? $item->declared_amount;
                $item->update([
                    'approved_amount' => $approved,
                    'status' => 'verified',
                ]);
                $approvedTotal += (float) $approved;
            }

            $declaration->update([
                'status' => TaxDeclaration::STATUS_VERIFIED,
                'approved_total' => round($approvedTotal, 2),
                'verified_at' => now(),
                'verified_by' => $actor?->id,
                'verifier_comments' => $comments,
            ]);

            $this->auditLogger->log($declaration, 'tax_declaration_verified', [
                'approved_total' => $declaration->approved_total,
            ], $actor);

            event(TaxDeclarationApproved::forModel($declaration->fresh(), [
                'employee_id' => $declaration->employee_id,
                'actor_id' => $actor?->id,
                'approved_total' => $declaration->approved_total,
            ]));

            $this->notifyEmployee(
                $declaration,
                __('Investment declaration approved'),
                __('Your tax investment declaration :number has been verified.', [
                    'number' => $declaration->declaration_number,
                ]),
                '/hrms/ess/tax/declarations/'.$declaration->id
            );

            return $declaration->fresh(['employee', 'items', 'financialYear']);
        });
    }

    public function reject(TaxDeclaration $declaration, string $reason, ?User $actor = null): TaxDeclaration
    {
        return DB::transaction(function () use ($declaration, $reason, $actor): TaxDeclaration {
            $declaration->refresh();

            if (! $declaration->canVerify()) {
                throw ValidationException::withMessages([
                    'declaration' => 'Only submitted declarations can be rejected.',
                ]);
            }

            $declaration->update([
                'status' => TaxDeclaration::STATUS_REJECTED,
                'rejection_reason' => $reason,
                'verified_at' => now(),
                'verified_by' => $actor?->id,
                'approved_total' => 0,
            ]);

            $this->auditLogger->log($declaration, 'tax_declaration_rejected', [
                'reason' => $reason,
            ], $actor);

            event(TaxDeclarationRejected::forModel($declaration->fresh(), [
                'employee_id' => $declaration->employee_id,
                'actor_id' => $actor?->id,
                'reason' => $reason,
            ]));

            $this->notifyEmployee(
                $declaration,
                __('Investment declaration rejected'),
                __('Your tax investment declaration :number was rejected: :reason', [
                    'number' => $declaration->declaration_number,
                    'reason' => $reason,
                ]),
                '/hrms/ess/tax/declarations/'.$declaration->id
            );

            return $declaration->fresh(['employee', 'items', 'financialYear']);
        });
    }

    /**
     * @return Collection<int, TaxDeclaration>
     */
    public function listForOrganization(?string $status = null): Collection
    {
        $query = TaxDeclaration::query()
            ->with(['employee', 'financialYear', 'items'])
            ->orderByDesc('id');

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    /**
     * @param  list<array{category: string, section?: string|null, label: string, declared_amount: float|int|string}>  $items
     */
    protected function syncItems(TaxDeclaration $declaration, array $items): void
    {
        $declaration->items()->delete();

        $categories = config('hrms.income_tax.declaration_categories', []);
        $declaredTotal = 0.0;

        foreach ($items as $row) {
            $category = (string) $row['category'];
            $categoryMeta = $categories[$category] ?? [];
            $section = $row['section'] ?? $categoryMeta['section'] ?? null;
            $label = (string) ($row['label'] ?? $categoryMeta['label'] ?? $category);
            $amount = round((float) ($row['declared_amount'] ?? 0), 2);

            TaxDeclarationItem::query()->create([
                'organization_id' => $declaration->organization_id,
                'tax_declaration_id' => $declaration->id,
                'category' => $category,
                'section' => $section,
                'label' => $label,
                'declared_amount' => $amount,
                'status' => 'draft',
            ]);

            $declaredTotal += $amount;
        }

        $declaration->update(['declared_total' => round($declaredTotal, 2)]);
    }

    protected function nextDeclarationNumber(): string
    {
        $organizationId = $this->tenantContext->id();
        $seq = TaxDeclaration::query()->where('organization_id', $organizationId)->count() + 1;

        return sprintf('TAX-%s-%04d', now()->format('Ymd'), $seq);
    }

    protected function formatDeclarationNumber(TaxDeclaration $declaration): string
    {
        return sprintf('TAX-DECL-%d-%d', $declaration->organization_id, $declaration->id);
    }

    protected function notifyEmployee(
        TaxDeclaration $declaration,
        string $title,
        string $message,
        ?string $url = null,
    ): void {
        try {
            $declaration->loadMissing('employee');
            $userId = $declaration->employee?->user_id;

            if (! $userId) {
                return;
            }

            $this->notificationService->send(
                $declaration->organization_id,
                (int) $userId,
                $title,
                $message,
                $url
            );
        } catch (\Throwable) {
            // In-app notification is best-effort.
        }
    }
}
