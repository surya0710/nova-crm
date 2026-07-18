<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_provider_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->unsignedBigInteger('marketing_provider_id');
            $table->string('sync_type', 50);
            $table->string('direction', 20);
            $table->string('status', 30)->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('records_processed')->default(0);
            $table->unsignedInteger('records_succeeded')->default(0);
            $table->unsignedInteger('records_failed')->default(0);
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('marketing_provider_id', 'mkt_prov_sync_runs_provider_fk')
                ->references('id')
                ->on('marketing_providers')
                ->cascadeOnDelete();

            $table->index(
                ['organization_id', 'marketing_provider_id', 'started_at'],
                'mkt_prov_sync_runs_org_provider_started_idx'
            );
            $table->index(
                ['organization_id', 'status'],
                'mkt_prov_sync_runs_org_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_provider_sync_runs');
    }
};
