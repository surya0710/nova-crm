<?php

namespace App\Services;

use App\Events\OpportunityCreated;
use App\Events\OpportunityLost;
use App\Events\OpportunityStageChanged;
use App\Events\OpportunityWon;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\OpportunityContact;
use App\Models\OpportunityProduct;
use App\Models\Product;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Opportunity lifecycle service.
 *
 * Owns stage transitions so marketing conversion hooks stay in the service
 * layer (Controllers → Services → Models), not in controllers or observers.
 */
class OpportunityService
{
    public function __construct(
        protected MarketingConversionService $conversions,
        protected MetadataEntityFormService $metadataForms,
    ) {}

    /**
     * @param  array{stage: string, won_at?: string|null, lost_reason?: string|null}  $data
     */
    public function updateStage(Opportunity $opportunity, array $data, ?User $actor = null): Opportunity
    {
        $stage = $data['stage'];

        if (! $opportunity->isOpen() && $stage !== $opportunity->stage) {
            throw ValidationException::withMessages([
                'stage' => __('Closed deals cannot be moved to another stage.'),
            ]);
        }

        return DB::transaction(function () use ($opportunity, $data, $stage, $actor) {
            $attributes = ['stage' => $stage];

            if ($stage === 'closed_won') {
                $attributes['won_at'] = $data['won_at'];
                $attributes['lost_reason'] = null;
            } elseif ($stage === 'closed_lost') {
                $attributes['lost_reason'] = $data['lost_reason'];
                $attributes['won_at'] = null;
            }

            $previousStage = $opportunity->stage;
            $opportunity->update($attributes);
            $opportunity = $opportunity->fresh(['lead', 'customer']);

            if ($stage === 'closed_won') {
                $this->conversions->recordOpportunityWon($opportunity);
            }

            if ($stage !== $previousStage) {
                $runtime = app(WorkflowRuntimeContext::class);
                event(OpportunityStageChanged::forModel($opportunity, [
                    'actor_id' => (int) ($actor?->id ?? $opportunity->created_by),
                    'previous_stage' => $previousStage,
                    'stage' => $stage,
                ], causationId: $runtime->causationId, depth: $runtime->causationId ? $runtime->depth + 1 : 0));

                if ($stage === 'closed_won') {
                    event(OpportunityWon::forModel($opportunity, [
                        'actor_id' => (int) ($actor?->id ?? $opportunity->created_by),
                    ], causationId: $runtime->causationId, depth: $runtime->causationId ? $runtime->depth + 1 : 0));
                } elseif ($stage === 'closed_lost') {
                    event(OpportunityLost::forModel($opportunity, [
                        'actor_id' => (int) ($actor?->id ?? $opportunity->created_by),
                        'lost_reason' => $opportunity->lost_reason,
                    ], causationId: $runtime->causationId, depth: $runtime->causationId ? $runtime->depth + 1 : 0));
                }
            }

            return $opportunity;
        });
    }

    public function create(array $data, User $actor, array $metadataValues = []): Opportunity
    {
        $related = $this->extractRelated($data);
        if (! array_key_exists('probability', $data) || $data['probability'] === null) {
            $data['probability'] = config('pipeline.stage_probabilities.'.($data['stage'] ?? 'qualification'), 20);
        }

        $opportunity = Opportunity::query()->create([...$data, 'created_by' => $actor->id]);
        $this->syncRelated($opportunity, $related);
        $this->metadataForms->persistValidatedValues($opportunity, $metadataValues);
        $opportunity = $opportunity->fresh();
        event(OpportunityCreated::forModel($opportunity, ['actor_id' => $actor->id]));

        return $opportunity;
    }

    public function update(Opportunity $opportunity, array $data, User $actor, array $metadataValues = []): Opportunity
    {
        $related = $this->extractRelated($data);
        $previousStage = $opportunity->stage;
        $opportunity->update($data);
        $this->syncRelated($opportunity, $related);
        $this->metadataForms->persistValidatedValues($opportunity, $metadataValues);
        $opportunity = $opportunity->fresh();
        if (array_key_exists('stage', $data) && $data['stage'] !== $previousStage) {
            $runtime = app(WorkflowRuntimeContext::class);
            event(OpportunityStageChanged::forModel($opportunity, [
                'actor_id' => $actor->id,
                'previous_stage' => $previousStage,
                'stage' => $opportunity->stage,
            ], causationId: $runtime->causationId, depth: $runtime->causationId ? $runtime->depth + 1 : 0));
        }

        return $opportunity;
    }

    public function syncNextActivity(Opportunity $opportunity): void
    {
        $next = $opportunity->activities()
            ->where('status', 'open')
            ->whereNotNull('due_at')
            ->orderBy('due_at')
            ->first();

        $opportunity->update([
            'next_activity_at' => $next?->due_at,
            'next_activity_type' => $next?->type,
            'next_activity_subject' => $next?->subject,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{contacts: array<int, mixed>, products: array<int, mixed>}
     */
    protected function extractRelated(array &$data): array
    {
        $hasContacts = array_key_exists('contacts', $data);
        $hasProducts = array_key_exists('products', $data);
        $contacts = $data['contacts'] ?? [];
        $products = $data['products'] ?? [];
        unset($data['contacts'], $data['products'], $data['contact_ids']);

        return [
            'contacts' => is_array($contacts) ? $contacts : [],
            'products' => is_array($products) ? $products : [],
            'has_contacts' => $hasContacts,
            'has_products' => $hasProducts,
        ];
    }

    /**
     * @param  array{contacts: array<int, mixed>, products: array<int, mixed>}  $related
     */
    protected function syncRelated(Opportunity $opportunity, array $related): void
    {
        if ($related['has_contacts'] ?? false) {
            $this->syncContacts($opportunity, $related['contacts']);
        }

        if ($related['has_products'] ?? false) {
            $this->syncProducts($opportunity, $related['products']);
        }
    }

    /**
     * @param  array<int, mixed>  $contacts
     */
    protected function syncContacts(Opportunity $opportunity, array $contacts): void
    {
        $keep = [];
        foreach ($contacts as $row) {
            $contactId = (int) (is_array($row) ? ($row['contact_id'] ?? $row['id'] ?? 0) : $row);
            if ($contactId <= 0) {
                continue;
            }

            $contact = Contact::query()
                ->where('id', $contactId)
                ->when($opportunity->customer_id, fn ($q) => $q->where('customer_id', $opportunity->customer_id))
                ->first();
            if (! $contact) {
                continue;
            }

            $record = OpportunityContact::query()->updateOrCreate(
                [
                    'opportunity_id' => $opportunity->id,
                    'contact_id' => $contact->id,
                ],
                [
                    'organization_id' => $opportunity->organization_id,
                    'role' => is_array($row) ? ($row['role'] ?? 'other') : 'other',
                ],
            );
            $keep[] = $record->id;
        }

        OpportunityContact::query()
            ->where('opportunity_id', $opportunity->id)
            ->whereNotIn('id', $keep)
            ->delete();
    }

    /**
     * @param  array<int, mixed>  $products
     */
    protected function syncProducts(Opportunity $opportunity, array $products): void
    {
        OpportunityProduct::query()->where('opportunity_id', $opportunity->id)->delete();

        foreach ($products as $row) {
            if (! is_array($row)) {
                continue;
            }

            $product = isset($row['product_id'])
                ? Product::query()->find($row['product_id'])
                : null;
            $name = trim((string) ($row['name'] ?? $product?->name ?? ''));
            if ($name === '') {
                continue;
            }

            $quantity = (float) ($row['quantity'] ?? 1);
            $unitPrice = (float) ($row['unit_price'] ?? $product?->unit_price ?? 0);

            OpportunityProduct::query()->create([
                'organization_id' => $opportunity->organization_id,
                'opportunity_id' => $opportunity->id,
                'product_id' => $product?->id,
                'name' => $name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'amount' => round($quantity * $unitPrice, 2),
            ]);
        }
    }
}
