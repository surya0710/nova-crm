<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_rating_scales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50);
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'perf_rating_scales_org_id_unique');
            $table->unique(['organization_id', 'code'], 'perf_rating_scales_org_code_unique');
            $table->index(['organization_id', 'is_active'], 'perf_rating_scales_org_active_idx');
            $table->index(['organization_id', 'is_default'], 'perf_rating_scales_org_default_idx');
        });

        Schema::create('performance_rating_scale_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('rating_scale_id');
            $table->unsignedTinyInteger('value');
            $table->string('label');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'perf_rating_scale_levels_org_id_unique');
            $table->unique(['organization_id', 'rating_scale_id', 'value'], 'perf_rating_scale_levels_org_scale_value_unique');
            $table->foreign(['organization_id', 'rating_scale_id'], 'perf_rating_scale_levels_org_scale_fk')
                ->references(['organization_id', 'id'])
                ->on('performance_rating_scales')
                ->cascadeOnDelete();
            $table->index(['organization_id', 'rating_scale_id'], 'perf_rating_scale_levels_org_scale_idx');
        });

        Schema::create('competency_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'competency_categories_org_id_unique');
            $table->unique(['organization_id', 'code'], 'competency_categories_org_code_unique');
            $table->index(['organization_id', 'is_active'], 'competency_categories_org_active_idx');
        });

        Schema::create('competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('competency_category_id');
            $table->string('name');
            $table->string('code', 50);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'competencies_org_id_unique');
            $table->unique(['organization_id', 'code'], 'competencies_org_code_unique');
            $table->foreign(['organization_id', 'competency_category_id'], 'competencies_org_category_fk')
                ->references(['organization_id', 'id'])
                ->on('competency_categories')
                ->restrictOnDelete();
            $table->index(['organization_id', 'competency_category_id'], 'competencies_org_category_idx');
            $table->index(['organization_id', 'is_active'], 'competencies_org_active_idx');
        });

        Schema::create('performance_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('cycle_type', 30);
            $table->string('status', 30)->default('draft');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'performance_cycles_org_id_unique');
            $table->index(['organization_id', 'status'], 'performance_cycles_org_status_idx');
            $table->index(['organization_id', 'start_date', 'end_date'], 'performance_cycles_org_dates_idx');
        });

        Schema::create('performance_review_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50);
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'perf_review_templates_org_id_unique');
            $table->unique(['organization_id', 'code'], 'perf_review_templates_org_code_unique');
            $table->index(['organization_id', 'is_active'], 'perf_review_templates_org_active_idx');
        });

        Schema::create('performance_review_template_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('review_template_id');
            $table->string('name');
            $table->text('instructions')->nullable();
            $table->decimal('weightage', 8, 4)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'perf_review_template_sections_org_id_unique');
            $table->foreign(['organization_id', 'review_template_id'], 'perf_review_template_sections_org_template_fk')
                ->references(['organization_id', 'id'])
                ->on('performance_review_templates')
                ->cascadeOnDelete();
            $table->index(['organization_id', 'review_template_id'], 'perf_review_template_sections_org_template_idx');
        });

        Schema::create('performance_review_template_competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('review_template_id');
            $table->unsignedBigInteger('section_id')->nullable();
            $table->unsignedBigInteger('competency_id');
            $table->decimal('weightage', 8, 4)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'perf_review_template_competencies_org_id_unique');
            $table->unique(
                ['organization_id', 'review_template_id', 'competency_id'],
                'perf_review_template_competencies_org_template_comp_unique'
            );
            $table->foreign(['organization_id', 'review_template_id'], 'prtc_org_template_fk')
                ->references(['organization_id', 'id'])
                ->on('performance_review_templates')
                ->cascadeOnDelete();
            $table->foreign('section_id', 'prtc_section_fk')
                ->references('id')
                ->on('performance_review_template_sections')
                ->nullOnDelete();
            $table->foreign(['organization_id', 'competency_id'], 'prtc_org_competency_fk')
                ->references(['organization_id', 'id'])
                ->on('competencies')
                ->restrictOnDelete();
            $table->index(['organization_id', 'review_template_id'], 'prtc_org_template_idx');
        });

        Schema::create('performance_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('default_review_frequency', 30)->default('annual');
            $table->unsignedBigInteger('rating_scale_id')->nullable();
            $table->decimal('goal_weighting', 8, 4)->default(50);
            $table->decimal('competency_weighting', 8, 4)->default(50);
            $table->string('review_visibility', 50)->default('employee_and_manager');
            $table->boolean('calibration_enabled')->default(false);
            $table->timestamps();

            $table->unique(['organization_id'], 'performance_configurations_org_unique');
            $table->unique(['organization_id', 'id'], 'performance_configurations_org_id_unique');
            $table->foreign('rating_scale_id', 'perf_config_rating_scale_fk')
                ->references('id')
                ->on('performance_rating_scales')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_configurations');
        Schema::dropIfExists('performance_review_template_competencies');
        Schema::dropIfExists('performance_review_template_sections');
        Schema::dropIfExists('performance_review_templates');
        Schema::dropIfExists('performance_cycles');
        Schema::dropIfExists('competencies');
        Schema::dropIfExists('competency_categories');
        Schema::dropIfExists('performance_rating_scale_levels');
        Schema::dropIfExists('performance_rating_scales');
    }
};
