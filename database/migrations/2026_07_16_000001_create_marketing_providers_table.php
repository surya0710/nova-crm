<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 50);
            $table->string('display_name');
            $table->string('status', 30)->default('disconnected');
            $table->string('external_account_id')->nullable();
            $table->json('capabilities')->nullable();
            $table->json('metadata')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_health_at')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'slug'], 'mkt_prov_org_slug_unique');
            $table->index(['organization_id', 'status'], 'mkt_prov_org_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_providers');
    }
};
