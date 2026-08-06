<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 50);
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'interview_stages_org_id_unique');
            $table->unique(['organization_id', 'slug'], 'interview_stages_org_slug_unique');
            $table->index(['organization_id', 'sort_order'], 'interview_stages_org_sort_idx');
        });

        Schema::create('evaluation_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'evaluation_templates_org_id_unique');
            $table->foreign('department_id', 'evaluation_templates_department_fk')
                ->references('id')
                ->on('hrms_departments')
                ->nullOnDelete();
            $table->foreign('designation_id', 'evaluation_templates_designation_fk')
                ->references('id')
                ->on('hrms_designations')
                ->nullOnDelete();
            $table->index(['organization_id', 'is_active'], 'evaluation_templates_org_active_idx');
        });

        Schema::create('evaluation_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('evaluation_template_id');
            $table->string('title');
            $table->unsignedSmallInteger('weight')->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'evaluation_sections_org_id_unique');
            $table->foreign(['organization_id', 'evaluation_template_id'], 'evaluation_sections_org_template_fk')
                ->references(['organization_id', 'id'])
                ->on('evaluation_templates')
                ->restrictOnDelete();
            $table->index(['organization_id', 'evaluation_template_id'], 'evaluation_sections_org_template_idx');
        });

        Schema::create('evaluation_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('evaluation_section_id');
            $table->text('question');
            $table->string('question_type', 30);
            $table->boolean('is_required')->default(true);
            $table->unsignedSmallInteger('weight')->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'evaluation_questions_org_id_unique');
            $table->foreign(['organization_id', 'evaluation_section_id'], 'evaluation_questions_org_section_fk')
                ->references(['organization_id', 'id'])
                ->on('evaluation_sections')
                ->restrictOnDelete();
            $table->index(['organization_id', 'evaluation_section_id'], 'evaluation_questions_org_section_idx');
        });

        Schema::create('interview_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('job_application_id');
            $table->unsignedBigInteger('interview_stage_id');
            $table->unsignedSmallInteger('round_number')->default(1);
            $table->string('interview_type', 30);
            $table->dateTime('scheduled_at')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('draft');
            $table->unsignedBigInteger('evaluation_template_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'interview_rounds_org_id_unique');
            $table->foreign(['organization_id', 'job_application_id'], 'interview_rounds_org_application_fk')
                ->references(['organization_id', 'id'])
                ->on('job_applications')
                ->restrictOnDelete();
            $table->foreign(['organization_id', 'interview_stage_id'], 'interview_rounds_org_stage_fk')
                ->references(['organization_id', 'id'])
                ->on('interview_stages')
                ->restrictOnDelete();
            $table->foreign('evaluation_template_id', 'interview_rounds_template_fk')
                ->references('id')
                ->on('evaluation_templates')
                ->nullOnDelete();
            $table->index(['organization_id', 'status'], 'interview_rounds_org_status_idx');
            $table->index(['organization_id', 'job_application_id'], 'interview_rounds_org_application_idx');
        });

        Schema::create('interview_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('interview_round_id');
            $table->string('participant_type', 30);
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('role', 30)->default('panel_member');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'interview_participants_org_id_unique');
            $table->foreign(['organization_id', 'interview_round_id'], 'interview_participants_org_round_fk')
                ->references(['organization_id', 'id'])
                ->on('interview_rounds')
                ->cascadeOnDelete();
            $table->foreign('employee_id', 'interview_participants_employee_fk')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
            $table->index(['organization_id', 'interview_round_id'], 'interview_participants_org_round_idx');
        });

        Schema::create('interview_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('interview_round_id');
            $table->unsignedBigInteger('interview_participant_id');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('comments')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'interview_feedback_org_id_unique');
            $table->unique(['organization_id', 'interview_round_id', 'interview_participant_id'], 'interview_feedback_org_round_participant_unique');
            $table->foreign(['organization_id', 'interview_round_id'], 'interview_feedback_org_round_fk')
                ->references(['organization_id', 'id'])
                ->on('interview_rounds')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'interview_participant_id'], 'interview_feedback_org_participant_fk')
                ->references(['organization_id', 'id'])
                ->on('interview_participants')
                ->cascadeOnDelete();
        });

        Schema::create('candidate_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('interview_round_id');
            $table->unsignedBigInteger('interview_participant_id');
            $table->unsignedBigInteger('evaluation_template_id')->nullable();
            $table->decimal('overall_rating', 4, 2)->nullable();
            $table->string('recommendation', 30)->nullable();
            $table->text('strengths')->nullable();
            $table->text('concerns')->nullable();
            $table->text('summary')->nullable();
            $table->string('status', 30)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'candidate_evaluations_org_id_unique');
            $table->unique(['organization_id', 'interview_round_id', 'interview_participant_id'], 'candidate_evaluations_org_round_participant_unique');
            $table->foreign(['organization_id', 'interview_round_id'], 'candidate_evaluations_org_round_fk')
                ->references(['organization_id', 'id'])
                ->on('interview_rounds')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'interview_participant_id'], 'candidate_evaluations_org_participant_fk')
                ->references(['organization_id', 'id'])
                ->on('interview_participants')
                ->cascadeOnDelete();
            $table->foreign('evaluation_template_id', 'candidate_evaluations_template_fk')
                ->references('id')
                ->on('evaluation_templates')
                ->nullOnDelete();
            $table->index(['organization_id', 'status'], 'candidate_evaluations_org_status_idx');
        });

        Schema::create('evaluation_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('candidate_evaluation_id');
            $table->unsignedBigInteger('evaluation_question_id');
            $table->text('response_value')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'evaluation_responses_org_id_unique');
            $table->unique(['organization_id', 'candidate_evaluation_id', 'evaluation_question_id'], 'evaluation_responses_org_eval_question_unique');
            $table->foreign(['organization_id', 'candidate_evaluation_id'], 'evaluation_responses_org_evaluation_fk')
                ->references(['organization_id', 'id'])
                ->on('candidate_evaluations')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'evaluation_question_id'], 'evaluation_responses_org_question_fk')
                ->references(['organization_id', 'id'])
                ->on('evaluation_questions')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_responses');
        Schema::dropIfExists('candidate_evaluations');
        Schema::dropIfExists('interview_feedback');
        Schema::dropIfExists('interview_participants');
        Schema::dropIfExists('interview_rounds');
        Schema::dropIfExists('evaluation_questions');
        Schema::dropIfExists('evaluation_sections');
        Schema::dropIfExists('evaluation_templates');
        Schema::dropIfExists('interview_stages');
    }
};
