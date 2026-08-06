<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_provider_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marketing_provider_id')->constrained('marketing_providers')->cascadeOnDelete();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->string('token_type', 50)->nullable();
            $table->json('scopes')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('marketing_provider_id', 'mkt_prov_cred_provider_unique');
            $table->index(['organization_id', 'expires_at'], 'mkt_prov_cred_org_expires_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_provider_credentials');
    }
};
