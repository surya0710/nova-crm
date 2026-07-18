<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_provider_imported_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->unsignedBigInteger('marketing_provider_id');
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->string('external_lead_id', 64);
            $table->string('external_form_id', 64)->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->foreign('marketing_provider_id', 'mkt_prov_imported_leads_prov_fk')
                ->references('id')
                ->on('marketing_providers')
                ->cascadeOnDelete();
            $table->foreign('lead_id', 'mkt_prov_imported_leads_lead_fk')
                ->references('id')
                ->on('leads')
                ->nullOnDelete();

            $table->unique(
                ['organization_id', 'marketing_provider_id', 'external_lead_id'],
                'mkt_prov_imported_leads_org_prov_ext_unique'
            );
            $table->index(
                ['organization_id', 'marketing_provider_id'],
                'mkt_prov_imported_leads_org_prov_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_provider_imported_leads');
    }
};
