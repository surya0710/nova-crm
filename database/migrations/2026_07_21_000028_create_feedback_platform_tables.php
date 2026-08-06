<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('performance_configurations', 'feedback_anonymous_enabled')) {
            Schema::table('performance_configurations', function (Blueprint $table) {
                $table->boolean('feedback_anonymous_enabled')->default(true)->after('calibration_enabled');
            });
        }

        if (! Schema::hasColumn('performance_configurations', 'feedback_anonymous_required')) {
            Schema::table('performance_configurations', function (Blueprint $table) {
                $table->boolean('feedback_anonymous_required')->default(false)->after('feedback_anonymous_enabled');
            });
        }

        if (Schema::hasTable('feedback_templates')) {
            return;
        }

        Schema::create('feedback_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'ft_org_id_unique');
            $table->index(['organization_id', 'is_active'], 'ft_org_active_idx');
        });

        Schema::create('feedback_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('feedback_template_id');
            $table->string('question_type', 30);
            $table->unsignedBigInteger('competency_id')->nullable();
            $table->string('question_text');
            $table->text('help_text')->nullable();
            $table->unsignedTinyInteger('scale_min')->nullable();
            $table->unsignedTinyInteger('scale_max')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'fq_org_id_unique');
            $table->foreign(['organization_id', 'feedback_template_id'], 'fq_org_template_fk')
                ->references(['organization_id', 'id'])
                ->on('feedback_templates')
                ->restrictOnDelete();
            $table->foreign('competency_id', 'fq_competency_fk')
                ->references('id')
                ->on('competencies')
                ->nullOnDelete();
            $table->index(['organization_id', 'feedback_template_id'], 'fq_org_template_idx');
        });

        Schema::create('feedback_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('performance_cycle_id');
            $table->unsignedBigInteger('feedback_template_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('due_date');
            $table->boolean('is_anonymous')->default(true);
            $table->string('status', 30)->default('draft');
            $table->json('summary')->nullable();
            $table->timestamp('summary_generated_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'fc_org_id_unique');
            $table->foreign(['organization_id', 'performance_cycle_id'], 'fc_org_cycle_fk')
                ->references(['organization_id', 'id'])
                ->on('performance_cycles')
                ->restrictOnDelete();
            $table->foreign(['organization_id', 'feedback_template_id'], 'fc_org_template_fk')
                ->references(['organization_id', 'id'])
                ->on('feedback_templates')
                ->restrictOnDelete();
            $table->index(['organization_id', 'status'], 'fc_org_status_idx');
            $table->index(['organization_id', 'performance_cycle_id'], 'fc_org_cycle_idx');
        });

        Schema::create('feedback_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('feedback_campaign_id');
            $table->unsignedBigInteger('performance_review_id')->nullable();
            $table->unsignedBigInteger('subject_employee_id');
            $table->unsignedBigInteger('participant_employee_id')->nullable();
            $table->string('external_name')->nullable();
            $table->string('external_email')->nullable();
            $table->string('participant_type', 30);
            $table->string('status', 30)->default('pending');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'fp_org_id_unique');
            $table->foreign(['organization_id', 'feedback_campaign_id'], 'fp_org_campaign_fk')
                ->references(['organization_id', 'id'])
                ->on('feedback_campaigns')
                ->restrictOnDelete();
            $table->foreign('performance_review_id', 'fp_review_fk')
                ->references('id')
                ->on('performance_reviews')
                ->nullOnDelete();
            $table->foreign(['organization_id', 'subject_employee_id'], 'fp_org_subject_fk')
                ->references(['organization_id', 'id'])
                ->on('employees')
                ->restrictOnDelete();
            $table->foreign('participant_employee_id', 'fp_participant_fk')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
            $table->unique(
                ['organization_id', 'feedback_campaign_id', 'subject_employee_id', 'participant_employee_id', 'participant_type'],
                'fp_org_campaign_subject_participant_type_unique'
            );
            $table->index(['organization_id', 'feedback_campaign_id'], 'fp_org_campaign_idx');
        });

        Schema::create('feedback_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('feedback_campaign_id');
            $table->unsignedBigInteger('feedback_participant_id');
            $table->unsignedBigInteger('performance_review_id')->nullable();
            $table->unsignedBigInteger('subject_employee_id');
            $table->unsignedBigInteger('participant_employee_id')->nullable();
            $table->string('participant_type', 30);
            $table->date('due_date')->nullable();
            $table->string('status', 30)->default('pending');
            $table->boolean('is_anonymous')->default(true);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'fr_org_id_unique');
            $table->foreign(['organization_id', 'feedback_campaign_id'], 'fr_org_campaign_fk')
                ->references(['organization_id', 'id'])
                ->on('feedback_campaigns')
                ->restrictOnDelete();
            $table->foreign(['organization_id', 'feedback_participant_id'], 'fr_org_participant_fk')
                ->references(['organization_id', 'id'])
                ->on('feedback_participants')
                ->restrictOnDelete();
            $table->foreign('performance_review_id', 'fr_review_fk')
                ->references('id')
                ->on('performance_reviews')
                ->nullOnDelete();
            $table->foreign(['organization_id', 'subject_employee_id'], 'fr_org_subject_fk')
                ->references(['organization_id', 'id'])
                ->on('employees')
                ->restrictOnDelete();
            $table->foreign('participant_employee_id', 'fr_participant_fk')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
            $table->unique(['organization_id', 'feedback_participant_id'], 'fr_org_participant_unique');
            $table->index(['organization_id', 'status'], 'fr_org_status_idx');
            $table->index(['organization_id', 'participant_employee_id'], 'fr_org_participant_idx');
        });

        Schema::create('feedback_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('feedback_request_id');
            $table->unsignedBigInteger('feedback_question_id');
            $table->decimal('rating', 8, 2)->nullable();
            $table->text('text_response')->nullable();
            $table->unsignedBigInteger('reviewer_employee_id')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'fresp_org_id_unique');
            $table->foreign(['organization_id', 'feedback_request_id'], 'fresp_org_request_fk')
                ->references(['organization_id', 'id'])
                ->on('feedback_requests')
                ->restrictOnDelete();
            $table->foreign(['organization_id', 'feedback_question_id'], 'fresp_org_question_fk')
                ->references(['organization_id', 'id'])
                ->on('feedback_questions')
                ->restrictOnDelete();
            $table->foreign('reviewer_employee_id', 'fresp_reviewer_fk')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
            $table->unique(['organization_id', 'feedback_request_id', 'feedback_question_id'], 'fresp_org_request_question_unique');
            $table->index(['organization_id', 'feedback_request_id'], 'fresp_org_request_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_responses');
        Schema::dropIfExists('feedback_requests');
        Schema::dropIfExists('feedback_participants');
        Schema::dropIfExists('feedback_campaigns');
        Schema::dropIfExists('feedback_questions');
        Schema::dropIfExists('feedback_templates');

        Schema::table('performance_configurations', function (Blueprint $table) {
            $table->dropColumn(['feedback_anonymous_enabled', 'feedback_anonymous_required']);
        });
    }
};
