<?php

namespace Database\Factories;

use App\Models\ImportSession;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportSession>
 */
class ImportSessionFactory extends Factory
{
    protected $model = ImportSession::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'entity_type' => 'demo',
            'original_filename' => 'import.csv',
            'stored_path' => 'imports/1/demo/'.fake()->uuid().'.csv',
            'disk' => 'local',
            'mime_type' => 'text/csv',
            'file_size' => 128,
            'uploaded_by' => null,
            'status' => ImportSession::STATUS_UPLOADED,
            'worksheet_name' => null,
            'column_mapping' => null,
            'detected_headers' => null,
            'validation_summary' => null,
            'started_at' => null,
            'completed_at' => null,
            'total_rows' => 0,
            'processed_rows' => 0,
            'created_count' => 0,
            'updated_count' => 0,
            'skipped_count' => 0,
            'failed_count' => 0,
            'last_error' => null,
            'metadata' => null,
        ];
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn () => [
            'organization_id' => $organization->id,
        ]);
    }

    public function uploadedBy(User $user): static
    {
        return $this->state(fn () => [
            'uploaded_by' => $user->id,
        ]);
    }

    public function ready(): static
    {
        return $this->state(fn () => [
            'status' => ImportSession::STATUS_READY,
            'started_at' => now(),
            'total_rows' => 2,
            'column_mapping' => ['email' => 'Email'],
            'detected_headers' => ['Email'],
            'validation_summary' => [
                'valid_rows' => 2,
                'invalid_rows' => 0,
                'unknown_columns' => [],
                'duplicate_columns' => [],
            ],
        ]);
    }

    public function failed(string $message = 'Import failed'): static
    {
        return $this->state(fn () => [
            'status' => ImportSession::STATUS_FAILED,
            'last_error' => $message,
            'completed_at' => now(),
        ]);
    }
}
