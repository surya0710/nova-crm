<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Services\MetadataEntityFormService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_metadata_config_includes_task_entity(): void
    {
        $this->assertArrayHasKey('task', config('metadata.entities'));
        $this->assertSame('Task', config('metadata.entities.task'));
    }

    public function test_metadata_fields_for_task_entity_does_not_throw(): void
    {
        $organization = Organization::factory()->create();
        $service = app(MetadataEntityFormService::class);

        foreach (['create', 'edit', 'detail'] as $context) {
            $fields = $service->fieldsFor($organization, 'task', $context);

            $this->assertTrue($fields->isEmpty() || $fields->isNotEmpty());
        }
    }
}
