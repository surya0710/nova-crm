<?php

use App\Models\Organization;
use App\Services\MetadataProjectionService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('metadata_value_projections') || ! Schema::hasTable('leads')) {
            return;
        }

        $projection = app(MetadataProjectionService::class);

        Organization::withoutGlobalScopes()
            ->pluck('id')
            ->each(fn (int $organizationId) => $projection->rebuildForOrganizationEntity($organizationId, 'lead'));
    }

    public function down(): void
    {
        // Forward-only data backfill. Projection rows remain rebuildable from canonical JSON.
    }
};
