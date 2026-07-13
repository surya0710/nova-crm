<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metadata_value_projections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('metadata_field_definition_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id');
            $table->string('field_key', 100);
            $table->string('field_type', 50);
            $table->string('value_hash', 64)->default('scalar');
            $table->string('value_string')->nullable();
            $table->text('value_text')->nullable();
            $table->bigInteger('value_number')->nullable();
            $table->decimal('value_decimal', 20, 6)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->date('value_date')->nullable();
            $table->dateTime('value_datetime')->nullable();
            $table->time('value_time')->nullable();
            $table->json('value_json')->nullable();
            $table->text('normalized_search_text')->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->string('definition_status', 30);
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('projected_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['organization_id', 'entity_type', 'entity_id', 'field_key', 'value_hash'],
                'mvp_org_entity_record_field_hash_unique'
            );
            $table->index(['organization_id', 'entity_type', 'entity_id'], 'mvp_org_entity_record_idx');
            $table->index(['organization_id', 'metadata_field_definition_id'], 'mvp_org_definition_idx');
            $table->index(['organization_id', 'entity_type', 'field_key', 'value_string', 'entity_id'], 'mvp_org_field_string_idx');
            $table->index(['organization_id', 'entity_type', 'field_key', 'value_number', 'entity_id'], 'mvp_org_field_number_idx');
            $table->index(['organization_id', 'entity_type', 'field_key', 'value_decimal', 'entity_id'], 'mvp_org_field_decimal_idx');
            $table->index(['organization_id', 'entity_type', 'field_key', 'value_boolean', 'entity_id'], 'mvp_org_field_boolean_idx');
            $table->index(['organization_id', 'entity_type', 'field_key', 'value_date', 'entity_id'], 'mvp_org_field_date_idx');
            $table->index(['organization_id', 'entity_type', 'field_key', 'value_datetime', 'entity_id'], 'mvp_org_field_datetime_idx');
            $table->index(['organization_id', 'entity_type', 'field_key', 'value_time', 'entity_id'], 'mvp_org_field_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metadata_value_projections');
    }
};
