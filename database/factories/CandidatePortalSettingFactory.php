<?php

namespace Database\Factories;

use App\Models\CandidatePortalSetting;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CandidatePortalSetting> */
class CandidatePortalSettingFactory extends Factory
{
    protected $model = CandidatePortalSetting::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'portal_enabled' => true,
            'allow_guest_apply' => true,
            'require_login_to_apply' => false,
        ];
    }
}
