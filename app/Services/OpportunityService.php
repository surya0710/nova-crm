<?php

namespace App\Services;

use App\Models\Opportunity;
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
    ) {}

    /**
     * @param  array{stage: string, won_at?: string|null, lost_reason?: string|null}  $data
     */
    public function updateStage(Opportunity $opportunity, array $data): Opportunity
    {
        $stage = $data['stage'];

        if (! $opportunity->isOpen() && $stage !== $opportunity->stage) {
            throw ValidationException::withMessages([
                'stage' => __('Closed deals cannot be moved to another stage.'),
            ]);
        }

        return DB::transaction(function () use ($opportunity, $data, $stage) {
            $attributes = ['stage' => $stage];

            if ($stage === 'closed_won') {
                $attributes['won_at'] = $data['won_at'];
                $attributes['lost_reason'] = null;
            } elseif ($stage === 'closed_lost') {
                $attributes['lost_reason'] = $data['lost_reason'];
                $attributes['won_at'] = null;
            }

            $opportunity->update($attributes);
            $opportunity = $opportunity->fresh(['lead', 'customer']);

            if ($stage === 'closed_won') {
                $this->conversions->recordOpportunityWon($opportunity);
            }

            return $opportunity;
        });
    }
}
