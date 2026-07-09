<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metadata_field_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type', 50);
            $table->string('key', 100);
            $table->string('label');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'entity_type', 'key'], 'mfg_org_entity_key_unique');
            $table->index(['organization_id', 'entity_type', 'sort_order'], 'mfg_org_entity_sort_idx');
        });

        Schema::create('metadata_field_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('metadata_field_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('entity_type', 50);
            $table->string('key', 100);
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('type', 50);
            $table->string('status', 30)->default('draft');
            $table->json('default_value')->nullable();
            $table->json('validation_rules')->nullable();
            $table->json('visibility_rules')->nullable();
            $table->json('display_rules')->nullable();
            $table->json('permission_rules')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_unique')->default(false);
            $table->boolean('is_searchable')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_sortable')->default(false);
            $table->boolean('is_reportable')->default(false);
            $table->boolean('is_exportable')->default(true);
            $table->boolean('is_api_visible')->default(true);
            $table->boolean('is_sensitive')->default(false);
            $table->boolean('is_system')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('source', 50)->default('manual');
            $table->string('source_type')->nullable();
            $table->string('source_identifier')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'entity_type', 'key'], 'mfd_org_entity_key_unique');
            $table->index(['organization_id', 'entity_type', 'status'], 'mfd_org_entity_status_idx');
            $table->index(['organization_id', 'entity_type', 'is_searchable'], 'mfd_org_entity_search_idx');
            $table->index(['organization_id', 'entity_type', 'is_reportable'], 'mfd_org_entity_report_idx');
        });

        Schema::create('metadata_field_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('metadata_field_definition_id')->constrained()->cascadeOnDelete();
            $table->string('value', 150);
            $table->string('label');
            $table->string('color', 30)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['metadata_field_definition_id', 'value'], 'mfo_definition_value_unique');
            $table->index(['organization_id', 'metadata_field_definition_id', 'sort_order'], 'mfo_org_definition_sort_idx');
        });

        Schema::create('metadata_field_layouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type', 50);
            $table->string('context', 50);
            $table->string('name');
            $table->json('layout')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'entity_type', 'context'], 'mfl_org_entity_context_idx');
        });

        Schema::create('metadata_field_layout_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('metadata_field_layout_id')->constrained()->cascadeOnDelete();
            $table->foreignId('metadata_field_definition_id');
            $table->string('tab_key')->nullable();
            $table->string('section_key')->nullable();
            $table->string('group_key')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('width', 30)->default('full');
            $table->json('visibility_rules')->nullable();
            $table->json('requirement_rules')->nullable();
            $table->json('readonly_rules')->nullable();
            $table->timestamps();

            $table->unique(['metadata_field_layout_id', 'metadata_field_definition_id'], 'mflf_layout_field_unique');
            $table->index(['organization_id', 'metadata_field_layout_id', 'sort_order'], 'mflf_org_layout_sort_idx');
            $table->foreign('metadata_field_definition_id', 'mflf_definition_fk')
                ->references('id')
                ->on('metadata_field_definitions')
                ->cascadeOnDelete();
        });

        Schema::create('metadata_field_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('metadata_field_definition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('action', 50);
            $table->boolean('allowed')->default(true);
            $table->timestamps();

            $table->unique(['metadata_field_definition_id', 'role_id', 'action'], 'mfp_definition_role_action_unique');
            $table->index(['organization_id', 'action'], 'mfp_org_action_idx');
        });

        Schema::create('metadata_field_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('metadata_field_definition_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('event', 50);
            $table->json('snapshot');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['metadata_field_definition_id', 'version'], 'mfv_definition_version_unique');
            $table->index(['organization_id', 'metadata_field_definition_id'], 'mfv_org_definition_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metadata_field_versions');
        Schema::dropIfExists('metadata_field_permissions');
        Schema::dropIfExists('metadata_field_layout_fields');
        Schema::dropIfExists('metadata_field_layouts');
        Schema::dropIfExists('metadata_field_options');
        Schema::dropIfExists('metadata_field_definitions');
        Schema::dropIfExists('metadata_field_groups');
    }
};
