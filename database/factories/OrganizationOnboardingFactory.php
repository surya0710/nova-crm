<?php

namespace Database\Factories;

use App\Models\OrganizationOnboarding;
use App\Models\PlatformUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationOnboarding>
 */
class OrganizationOnboardingFactory extends Factory
{
    protected $model = OrganizationOnboarding::class;

    public function definition(): array
    {
        return [
            'organization_id' => null,
            'initiated_by_platform_user_id' => PlatformUser::factory(),
            'status' => OrganizationOnboarding::STATUS_DRAFT,
            'current_step' => 'organization',
            'progress_percent' => 0,
            'completed_steps' => [],
            'skipped_steps' => [],
            'step_data' => [],
            'checklist' => [],
            'metadata' => [],
            'started_at' => now(),
        ];
    }
}
