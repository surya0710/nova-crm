<?php

namespace Database\Factories;

use App\Models\ExportSession;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ExportSession>
 */
class ExportSessionFactory extends Factory
{
    protected $model = ExportSession::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'initiated_by' => User::factory(),
            'module' => 'crm',
            'entity_type' => 'lead',
            'format' => 'csv',
            'selection_mode' => 'ids',
            'status' => ExportSession::STATUS_PENDING,
            'total_count' => 0,
            'processed_count' => 0,
            'record_ids' => [],
            'columns' => ['name', 'email'],
            'disk' => 'local',
            'download_token' => Str::random(48),
            'download_expires_at' => now()->addHours(72),
        ];
    }
}
