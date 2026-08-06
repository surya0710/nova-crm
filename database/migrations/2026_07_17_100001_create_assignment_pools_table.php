<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_pools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('strategy', 50);
            $table->unsignedInteger('rotation_position')->default(0);
            $table->timestamps();

            $table->index(['organization_id', 'is_active'], 'assign_pools_org_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_pools');
    }
};
