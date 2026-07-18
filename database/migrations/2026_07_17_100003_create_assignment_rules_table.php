<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('entity_type', 50);
            $table->unsignedInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->string('strategy', 50)->nullable();
            $table->foreignId('assignment_pool_id')->nullable()->constrained()->nullOnDelete();
            $table->json('conditions')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'entity_type', 'is_active', 'priority'], 'assign_rules_match_idx');
            $table->index(['organization_id', 'entity_type', 'is_default'], 'assign_rules_default_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_rules');
    }
};
