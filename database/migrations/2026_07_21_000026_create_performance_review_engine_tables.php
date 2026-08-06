<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_review_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('performance_cycle_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('review_template_id');
            $table->unsignedBigInteger('primary_reviewer_id')->nullable();
            $table->date('due_date')->nullable();
            $table->string('review_type', 30)->default('manager');
            $table->string('status', 30)->default('planned');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'pra_org_id_unique');
            $table->foreign(['organization_id', 'performance_cycle_id'], 'pra_org_cycle_fk')
                ->references(['organization_id', 'id'])
                ->on('performance_cycles')
                ->restrictOnDelete();
            $table->foreign(['organization_id', 'employee_id'], 'pra_org_employee_fk')
                ->references(['organization_id', 'id'])
                ->on('employees')
                ->restrictOnDelete();
            $table->foreign(['organization_id', 'review_template_id'], 'pra_org_template_fk')
                ->references(['organization_id', 'id'])
                ->on('performance_review_templates')
                ->restrictOnDelete();
            $table->foreign('primary_reviewer_id', 'pra_reviewer_fk')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
            $table->unique(
                ['organization_id', 'performance_cycle_id', 'employee_id', 'review_type'],
                'pra_org_cycle_employee_type_unique'
            );
            $table->index(['organization_id', 'status'], 'pra_org_status_idx');
            $table->index(['organization_id', 'primary_reviewer_id'], 'pra_org_reviewer_idx');
            $table->index(['organization_id', 'due_date'], 'pra_org_due_idx');
        });

        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('review_assignment_id');
            $table->unsignedBigInteger('performance_cycle_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('review_template_id');
            $table->unsignedBigInteger('reviewer_id')->nullable();
            $table->string('review_type', 30)->default('manager');
            $table->string('status', 30)->default('draft');
            $table->text('overall_comments')->nullable();
            $table->text('development_notes')->nullable();
            $table->text('strengths')->nullable();
            $table->text('improvement_areas')->nullable();
            $table->json('snapshot')->nullable();
            $table->string('snapshot_hash', 64)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'pr_org_id_unique');
            $table->unique(['organization_id', 'review_assignment_id'], 'pr_org_assignment_unique');
            $table->foreign(['organization_id', 'review_assignment_id'], 'pr_org_assignment_fk')
                ->references(['organization_id', 'id'])
                ->on('performance_review_assignments')
                ->restrictOnDelete();
            $table->foreign(['organization_id', 'performance_cycle_id'], 'pr_org_cycle_fk')
                ->references(['organization_id', 'id'])
                ->on('performance_cycles')
                ->restrictOnDelete();
            $table->foreign(['organization_id', 'employee_id'], 'pr_org_employee_fk')
                ->references(['organization_id', 'id'])
                ->on('employees')
                ->restrictOnDelete();
            $table->foreign(['organization_id', 'review_template_id'], 'pr_org_template_fk')
                ->references(['organization_id', 'id'])
                ->on('performance_review_templates')
                ->restrictOnDelete();
            $table->foreign('reviewer_id', 'pr_reviewer_fk')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
            $table->index(['organization_id', 'status'], 'pr_org_status_idx');
            $table->index(['organization_id', 'employee_id', 'status'], 'pr_org_employee_status_idx');
            $table->index(['organization_id', 'reviewer_id'], 'pr_org_reviewer_idx');
        });

        Schema::create('performance_review_competency_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('performance_review_id');
            $table->unsignedBigInteger('competency_id')->nullable();
            $table->string('competency_name');
            $table->string('competency_code', 50)->nullable();
            $table->string('section_name')->nullable();
            $table->decimal('weightage', 8, 4)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->decimal('rating', 8, 2)->nullable();
            $table->text('comments')->nullable();
            $table->text('reviewer_notes')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'prce_org_id_unique');
            $table->foreign('organization_id', 'prce_org_fk')
                ->references('id')
                ->on('organizations')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'performance_review_id'], 'prce_org_review_fk')
                ->references(['organization_id', 'id'])
                ->on('performance_reviews')
                ->cascadeOnDelete();
            $table->foreign('competency_id', 'prce_competency_fk')
                ->references('id')
                ->on('competencies')
                ->nullOnDelete();
            $table->index(['organization_id', 'performance_review_id'], 'prce_org_review_idx');
        });

        Schema::create('performance_review_goal_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('performance_review_id');
            $table->unsignedBigInteger('goal_id')->nullable();
            $table->string('goal_title');
            $table->text('goal_description')->nullable();
            $table->string('measurement_type', 30)->nullable();
            $table->decimal('target_value', 15, 4)->nullable();
            $table->decimal('current_value', 15, 4)->nullable();
            $table->decimal('achievement_percentage', 8, 2)->default(0);
            $table->decimal('weight', 8, 2)->default(0);
            $table->string('completion_status', 30)->nullable();
            $table->string('kpi_name')->nullable();
            $table->string('kpi_code', 50)->nullable();
            $table->decimal('kpi_value', 15, 4)->nullable();
            $table->text('comments')->nullable();
            $table->decimal('rating', 8, 2)->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'prge_org_id_unique');
            $table->foreign('organization_id', 'prge_org_fk')
                ->references('id')
                ->on('organizations')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'performance_review_id'], 'prge_org_review_fk')
                ->references(['organization_id', 'id'])
                ->on('performance_reviews')
                ->cascadeOnDelete();
            $table->foreign('goal_id', 'prge_goal_fk')
                ->references('id')
                ->on('goals')
                ->nullOnDelete();
            $table->index(['organization_id', 'performance_review_id'], 'prge_org_review_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_review_goal_evaluations');
        Schema::dropIfExists('performance_review_competency_evaluations');
        Schema::dropIfExists('performance_reviews');
        Schema::dropIfExists('performance_review_assignments');
    }
};
