<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_provider_uploaded_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->unsignedBigInteger('marketing_provider_id');
            $table->unsignedBigInteger('marketing_conversion_id');
            $table->string('external_event_id', 128)->nullable();
            $table->string('provider_event_name', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();

            $table->foreign('marketing_provider_id', 'mkt_prov_uploaded_conv_prov_fk')
                ->references('id')
                ->on('marketing_providers')
                ->cascadeOnDelete();
            $table->foreign('marketing_conversion_id', 'mkt_prov_uploaded_conv_conv_fk')
                ->references('id')
                ->on('marketing_conversions')
                ->cascadeOnDelete();

            $table->unique(
                ['organization_id', 'marketing_provider_id', 'marketing_conversion_id'],
                'mkt_prov_uploaded_conv_org_prov_conv_unique'
            );
            $table->index(
                ['organization_id', 'marketing_provider_id', 'uploaded_at'],
                'mkt_prov_uploaded_conv_org_prov_at_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_provider_uploaded_conversions');
    }
};
