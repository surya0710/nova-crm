<?php

namespace App\Services;

use App\Events\OpportunityCreated;
use App\Events\OpportunityStageChanged;
use App\Models\Opportunity;
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
            }

            return $opportunity;
        });
    }

    public function create(array $data, User $actor, array $metadataValues = []): Opportunity
    {
        $opportunity = Opportunity::query()->create([...$data, 'created_by' => $actor->id]);
        $this->metadataForms->persistValidatedValues($opportunity, $metadataValues);
        $opportunity = $opportunity->fresh();
        event(OpportunityCreated::forModel($opportunity, ['actor_id' => $actor->id]));

        return $opportunity;
    }

    public function update(Opportunity $opportunity, array $data, User $actor, array $metadataValues = []): Opportunity
    {
        $previousStage = $opportunity->stage;
        $opportunity->update($data);
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
}
