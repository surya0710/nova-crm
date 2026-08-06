<?php

namespace Database\Factories;

use App\Models\FeedbackCampaign;
use App\Models\FeedbackTemplate;
use App\Models\Organization;
use App\Models\PerformanceCycle;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FeedbackCampaign> */
class FeedbackCampaignFactory extends Factory
{
    protected $model = FeedbackCampaign::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'performance_cycle_id' => PerformanceCycle::factory(),
            'feedback_template_id' => FeedbackTemplate::factory(),
            'name' => fake()->words(3, true).' 360° Feedback',
            'description' => fake()->sentence(),
            'start_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'is_anonymous' => true,
            'status' => 'draft',
            'summary' => null,
            'summary_generated_at' => null,
            'created_by' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (FeedbackCampaign $campaign) {
            $this->alignOrganization($campaign);
        })->afterCreating(function (FeedbackCampaign $campaign) {
            $this->alignOrganization($campaign, true);
        });
    }

    protected function alignOrganization(FeedbackCampaign $campaign, bool $persist = false): void
    {
        $cycle = $campaign->performance_cycle_id
            ? PerformanceCycle::query()->find($campaign->performance_cycle_id)
            : null;

        if ($cycle && $campaign->organization_id !== $cycle->organization_id) {
            $campaign->organization_id = $cycle->organization_id;
            if ($persist) {
                $campaign->saveQuietly();
            }
        }
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }
}
