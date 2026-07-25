<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('organization_modules')) {
            return;
        }

        Schema::create('organization_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('module_key', 100);
            $table->boolean('is_enabled')->default(true);
            $table->string('source', 40)->default('subscription'); // subscription|trial|addon|manual
            $table->boolean('included_in_subscription')->default(true);
            $table->boolean('is_trial')->default(false);
            $table->boolean('is_addon')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'module_key']);
            $table->index(['organization_id', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_modules');
    }
};
