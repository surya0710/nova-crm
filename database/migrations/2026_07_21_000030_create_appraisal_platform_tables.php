<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appraisal_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('performance_cycle_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 30)->default('draft');
            $table->json('rating_weights')->nullable();
            $table->json('talent_matrix_config')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'aps_org_id_unique');
            $table->foreign(['organization_id', 'performance_cycle_id'], 'aps_org_cycle_fk')
                ->references(['organization_id', 'id'])
                ->on('performance_cycles')
                ->restrictOnDelete();
            $table->index(['organization_id', 'status'], 'aps_org_status_idx');
            $table->index(['organization_id', 'performance_cycle_id'], 'aps_org_cycle_idx');
        });

        Schema::create('employee_appraisals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('appraisal_session_id');
            $table->unsignedBigInteger('performance_cycle_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('manager_employee_id')->nullable();
            $table->string('status', 30)->default('generated');
            $table->text('final_comments')->nullable();
            $table->text('overall_summary')->nullable();
            $table->text('manager_recommendation')->nullable();
            $table->text('hr_recommendation')->nullable();
            $table->text('executive_notes')->nullable();
            $table->decimal('manager_rating', 8, 2)->nullable();
            $table->decimal('calibrated_rating', 8, 2)->nullable();
            $table->decimal('final_rating', 8, 2)->nullable();
            $table->json('rating_breakdown')->nullable();
            $table->json('rating_calculation_snapshot')->nullable();
            $table->unsignedBigInteger('appraisal_calibration_id')->nullable();
            $table->text('calibration_comments')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('hr_reviewed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'ea_org_id_unique');
            $table->unique(
                ['organization_id', 'appraisal_session_id', 'employee_id'],
                'ea_org_session_employee_unique'
            );
            $table->foreign(['organization_id', 'appraisal_session_id'], 'ea_org_session_fk')
                ->references(['organization_id', 'id'])
                ->on('appraisal_sessions')
                ->restrictOnDelete();
            $table->foreign(['organization_id', 'performance_cycle_id'], 'ea_org_cycle_fk')
                ->references(['organization_id', 'id'])
                ->on('performance_cycles')
                ->restrictOnDelete();
            $table->foreign(['organization_id', 'employee_id'], 'ea_org_employee_fk')
                ->references(['organization_id', 'id'])
                ->on('employees')
                ->restrictOnDelete();
            $table->foreign('manager_employee_id', 'ea_manager_fk')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
            $table->index(['organization_id', 'status'], 'ea_org_status_idx');
            $table->index(['organization_id', 'manager_employee_id'], 'ea_org_manager_idx');
        });

        Schema::create('appraisal_development_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_appraisal_id');
            $table->text('strengths')->nullable();
            $table->text('improvement_areas')->nullable();
            $table->text('learning_objectives')->nullable();
            $table->text('required_training')->nullable();
            $table->text('career_aspirations')->nullable();
            $table->date('target_completion_date')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'adp_org_id_unique');
            $table->unique(['organization_id', 'employee_appraisal_id'], 'adp_org_appraisal_unique');
            $table->foreign(['organization_id', 'employee_appraisal_id'], 'adp_org_appraisal_fk')
                ->references(['organization_id', 'id'])
                ->on('employee_appraisals')
                ->cascadeOnDelete();
        });

        Schema::create('appraisal_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_appraisal_id');
            $table->string('recommendation_type', 30);
            $table->string('promotion_recommendation', 30)->nullable();
            $table->unsignedBigInteger('target_designation_id')->nullable();
            $table->date('effective_date')->nullable();
            $table->text('justification')->nullable();
            $table->decimal('increment_percent', 8, 2)->nullable();
            $table->decimal('bonus_recommendation', 12, 2)->nullable();
            $table->decimal('equity_recommendation', 12, 2)->nullable();
            $table->text('adjustment_notes')->nullable();
            $table->boolean('critical_role_flag')->default(false);
            $table->string('readiness_level', 30)->nullable();
            $table->text('succession_notes')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'ar_org_id_unique');
            $table->unique(
                ['organization_id', 'employee_appraisal_id', 'recommendation_type'],
                'ar_org_appraisal_type_unique'
            );
            $table->foreign(['organization_id', 'employee_appraisal_id'], 'ar_org_appraisal_fk')
                ->references(['organization_id', 'id'])
                ->on('employee_appraisals')
                ->cascadeOnDelete();
            $table->foreign('target_designation_id', 'ar_designation_fk')
                ->references('id')
                ->on('hrms_designations')
                ->nullOnDelete();
        });

        Schema::create('appraisal_calibrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('appraisal_session_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 30)->default('draft');
            $table->json('participant_employee_ids')->nullable();
            $table->json('adjustments')->nullable();
            $table->text('session_comments')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'ac_org_id_unique');
            $table->foreign(['organization_id', 'appraisal_session_id'], 'ac_org_session_fk')
                ->references(['organization_id', 'id'])
                ->on('appraisal_sessions')
                ->restrictOnDelete();
            $table->index(['organization_id', 'status'], 'ac_org_status_idx');
        });

        Schema::table('employee_appraisals', function (Blueprint $table) {
            $table->foreign('appraisal_calibration_id', 'ea_calibration_fk')
                ->references('id')
                ->on('appraisal_calibrations')
                ->nullOnDelete();
        });

        Schema::create('talent_matrix_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('appraisal_session_id');
            $table->unsignedBigInteger('employee_appraisal_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedTinyInteger('performance_band')->default(2);
            $table->unsignedTinyInteger('potential_band')->default(2);
            $table->decimal('performance_score', 8, 2)->nullable();
            $table->decimal('potential_score', 8, 2)->nullable();
            $table->string('classification', 60)->nullable();
            $table->json('matrix_config_snapshot')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'tme_org_id_unique');
            $table->unique(
                ['organization_id', 'appraisal_session_id', 'employee_id'],
                'tme_org_session_employee_unique'
            );
            $table->foreign(['organization_id', 'appraisal_session_id'], 'tme_org_session_fk')
                ->references(['organization_id', 'id'])
                ->on('appraisal_sessions')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'employee_appraisal_id'], 'tme_org_appraisal_fk')
                ->references(['organization_id', 'id'])
                ->on('employee_appraisals')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'employee_id'], 'tme_org_employee_fk')
                ->references(['organization_id', 'id'])
                ->on('employees')
                ->restrictOnDelete();
            $table->index(['organization_id', 'classification'], 'tme_org_class_idx');
        });
    }

    public function down(): void
    {
        Schema::table('employee_appraisals', function (Blueprint $table) {
            $table->dropForeign('ea_calibration_fk');
        });

        Schema::dropIfExists('talent_matrix_entries');
        Schema::dropIfExists('appraisal_calibrations');
        Schema::dropIfExists('appraisal_recommendations');
        Schema::dropIfExists('appraisal_development_plans');
        Schema::dropIfExists('employee_appraisals');
        Schema::dropIfExists('appraisal_sessions');
    }
};
