<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_provider_lead_import_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->unsignedBigInteger('marketing_provider_id');
            $table->unsignedBigInteger('triggered_by')->nullable();
            $table->unsignedInteger('imported_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->string('status', 30)->default('completed');
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->foreign('marketing_provider_id', 'mkt_prov_import_runs_prov_fk')
                ->references('id')
                ->on('marketing_providers')
                ->cascadeOnDelete();
            $table->foreign('triggered_by', 'mkt_prov_import_runs_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(
                ['organization_id', 'marketing_provider_id', 'imported_at'],
                'mkt_prov_import_runs_org_prov_at_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_provider_lead_import_runs');
    }
};
