<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_onboardings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('initiated_by_platform_user_id')->constrained('platform_users')->cascadeOnDelete();
            $table->string('status', 32)->default('draft')->index();
            $table->string('current_step', 64)->default('organization');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->json('completed_steps')->nullable();
            $table->json('skipped_steps')->nullable();
            $table->json('step_data')->nullable();
            $table->json('checklist')->nullable();
            $table->json('metadata')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'updated_at']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_onboardings');
    }
};
