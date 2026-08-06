<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_pool_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assignment_pool_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('weight')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['assignment_pool_id', 'user_id'], 'assign_pool_member_unique');
            $table->index(['assignment_pool_id', 'is_active'], 'assign_pool_members_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_pool_members');
    }
};
