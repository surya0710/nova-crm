<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_provider_lead_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marketing_provider_id')->constrained('marketing_providers')->cascadeOnDelete();
            $table->string('external_form_id', 64);
            $table->string('external_page_id', 64)->nullable();
            $table->string('name')->nullable();
            $table->string('status', 30)->default('active');
            $table->string('locale', 32)->nullable();
            $table->json('questions')->nullable();
            $table->json('raw_metadata')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['organization_id', 'marketing_provider_id', 'external_form_id'],
                'mkt_prov_lead_forms_org_prov_ext_unique'
            );
            $table->index(
                ['organization_id', 'marketing_provider_id', 'status'],
                'mkt_prov_lead_forms_org_prov_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_provider_lead_forms');
    }
};
