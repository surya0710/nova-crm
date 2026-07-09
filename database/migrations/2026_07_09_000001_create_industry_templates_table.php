<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('industry_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('industry')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('status')->default('draft')->index();
            $table->string('visibility')->default('internal')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('current_version_id')->nullable()->index();
            $table->json('draft_payload')->nullable();
            $table->unsignedInteger('draft_schema_version')->default(1);
            $table->foreignId('created_by_platform_user_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->foreignId('updated_by_platform_user_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->foreignId('published_by_platform_user_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('archived_by_platform_user_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('industry_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('industry_template_id')->constrained('industry_templates')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->unsignedInteger('schema_version')->default(1);
            $table->json('payload');
            $table->string('payload_hash', 64);
            $table->text('changelog')->nullable();
            $table->string('status')->default('published');
            $table->foreignId('published_by_platform_user_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->timestamp('published_at');
            $table->timestamps();

            $table->unique(['industry_template_id', 'version']);
            $table->index(['industry_template_id', 'status']);
            $table->index('payload_hash');
        });

        Schema::table('industry_templates', function (Blueprint $table) {
            $table->foreign('current_version_id')
                ->references('id')
                ->on('industry_template_versions')
                ->nullOnDelete();
        });

        Schema::create('organization_template_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('industry_template_id');
            $table->foreignId('industry_template_version_id');
            $table->foreignId('applied_by_platform_user_id')->nullable();
            $table->string('application_type')->default('initial_onboarding');
            $table->string('status')->default('applied');
            $table->string('payload_hash', 64);
            $table->json('applied_sections')->nullable();
            $table->json('skipped_sections')->nullable();
            $table->json('summary')->nullable();
            $table->timestamp('applied_at');
            $table->timestamps();

            $table->unique(['organization_id', 'application_type'], 'org_template_app_org_type_unique');
            $table->index('industry_template_id', 'org_template_app_template_index');
            $table->index('industry_template_version_id', 'org_template_app_version_index');
            $table->index('applied_at', 'org_template_app_applied_at_index');
            $table->foreign('industry_template_id', 'org_template_app_template_fk')
                ->references('id')
                ->on('industry_templates')
                ->restrictOnDelete();
            $table->foreign('industry_template_version_id', 'org_template_app_version_fk')
                ->references('id')
                ->on('industry_template_versions')
                ->restrictOnDelete();
            $table->foreign('applied_by_platform_user_id', 'org_template_app_actor_fk')
                ->references('id')
                ->on('platform_users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_template_applications');

        Schema::table('industry_templates', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });

        Schema::dropIfExists('industry_template_versions');
        Schema::dropIfExists('industry_templates');
    }
};
