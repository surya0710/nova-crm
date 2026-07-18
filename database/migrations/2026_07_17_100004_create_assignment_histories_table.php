<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id');
            $table->foreignId('previous_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('new_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('strategy', 50)->nullable();
            $table->foreignId('assignment_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assignment_pool_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 50);
            $table->timestamp('assigned_at');
            $table->timestamps();

            $table->index(['organization_id', 'entity_type', 'entity_id'], 'assign_hist_entity_idx');
            $table->index(['organization_id', 'assigned_at'], 'assign_hist_org_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_histories');
    }
};
