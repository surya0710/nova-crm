<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->string('employment_type', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->longText('template_content');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'offer_templates_org_id_unique');
            $table->foreign('department_id', 'offer_templates_department_fk')
                ->references('id')
                ->on('hrms_departments')
                ->nullOnDelete();
            $table->foreign('designation_id', 'offer_templates_designation_fk')
                ->references('id')
                ->on('hrms_designations')
                ->nullOnDelete();
            $table->index(['organization_id', 'is_active'], 'offer_templates_org_active_idx');
        });

        Schema::create('offer_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('job_application_id');
            $table->unsignedBigInteger('offer_template_id')->nullable();
            $table->unsignedBigInteger('reporting_manager_id')->nullable();
            $table->decimal('proposed_salary', 15, 2);
            $table->decimal('variable_pay', 15, 2)->nullable();
            $table->text('benefits')->nullable();
            $table->date('joining_date');
            $table->date('expiry_date');
            $table->string('status', 30)->default('draft');
            $table->longText('generated_content')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'offer_letters_org_id_unique');
            $table->foreign(['organization_id', 'candidate_id'], 'offer_letters_org_candidate_fk')
                ->references(['organization_id', 'id'])
                ->on('candidates')
                ->restrictOnDelete();
            $table->foreign(['organization_id', 'job_application_id'], 'offer_letters_org_application_fk')
                ->references(['organization_id', 'id'])
                ->on('job_applications')
                ->restrictOnDelete();
            $table->foreign('offer_template_id', 'offer_letters_template_fk')
                ->references('id')
                ->on('offer_templates')
                ->nullOnDelete();
            $table->foreign('reporting_manager_id', 'offer_letters_reporting_manager_fk')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
            $table->index(['organization_id', 'status'], 'offer_letters_org_status_idx');
            $table->index(['organization_id', 'job_application_id'], 'offer_letters_org_application_idx');
        });

        Schema::create('offer_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('offer_letter_id');
            $table->foreignId('approver_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 30)->default('pending');
            $table->text('comments')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'offer_approvals_org_id_unique');
            $table->foreign(['organization_id', 'offer_letter_id'], 'offer_approvals_org_offer_fk')
                ->references(['organization_id', 'id'])
                ->on('offer_letters')
                ->cascadeOnDelete();
            $table->index(['organization_id', 'status'], 'offer_approvals_org_status_idx');
            $table->index(['organization_id', 'approver_id'], 'offer_approvals_org_approver_idx');
        });

        Schema::create('offer_negotiations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('offer_letter_id');
            $table->decimal('requested_salary', 15, 2)->nullable();
            $table->date('requested_joining_date')->nullable();
            $table->text('candidate_comments')->nullable();
            $table->text('recruiter_notes')->nullable();
            $table->string('outcome', 30)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'offer_negotiations_org_id_unique');
            $table->foreign(['organization_id', 'offer_letter_id'], 'offer_negotiations_org_offer_fk')
                ->references(['organization_id', 'id'])
                ->on('offer_letters')
                ->cascadeOnDelete();
            $table->index(['organization_id', 'offer_letter_id'], 'offer_negotiations_org_offer_idx');
        });

        Schema::create('hiring_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('job_application_id');
            $table->string('recommendation', 30);
            $table->date('decision_date');
            $table->foreignId('decision_by')->constrained('users')->restrictOnDelete();
            $table->text('final_notes')->nullable();
            $table->boolean('onboarding_recommended')->default(false);
            $table->timestamp('onboarding_recommended_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'hiring_decisions_org_id_unique');
            $table->foreign(['organization_id', 'job_application_id'], 'hiring_decisions_org_application_fk')
                ->references(['organization_id', 'id'])
                ->on('job_applications')
                ->restrictOnDelete();
            $table->index(['organization_id', 'recommendation'], 'hiring_decisions_org_recommendation_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hiring_decisions');
        Schema::dropIfExists('offer_negotiations');
        Schema::dropIfExists('offer_approvals');
        Schema::dropIfExists('offer_letters');
        Schema::dropIfExists('offer_templates');
    }
};
