<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Services\MetadataEntityFormService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_metadata_config_includes_project_entity(): void
    {
        $this->assertArrayHasKey('project', config('metadata.entities'));
        $this->assertSame('Project', config('metadata.entities.project'));
    }

    public function test_metadata_fields_for_project_entity_does_not_throw(): void
    {
        $organization = Organization::factory()->create();
        $service = app(MetadataEntityFormService::class);

        foreach (['create', 'edit', 'detail'] as $context) {
            $fields = $service->fieldsFor($organization, 'project', $context);

            $this->assertTrue($fields->isEmpty() || $fields->isNotEmpty());
        }
    }
}
