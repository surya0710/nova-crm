<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_filters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('filter_definition');
            $table->string('visibility', 20)->default('private');
            $table->string('validation_status', 20)->default('valid');
            $table->json('validation_errors')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'entity_type', 'visibility'], 'sf_org_entity_visibility_idx');
            $table->unique(['organization_id', 'created_by', 'entity_type', 'name'], 'sf_owner_entity_name_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_filters');
    }
};
