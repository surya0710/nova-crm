<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_requisitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('designation_id');
            $table->string('employment_type', 30);
            $table->unsignedBigInteger('hiring_manager_id')->nullable();
            $table->unsignedSmallInteger('number_of_positions')->default(1);
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('business_justification');
            $table->date('target_joining_date')->nullable();
            $table->decimal('budget', 15, 2)->nullable();
            $table->string('status', 30)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'job_requisitions_org_id_unique');
            $table->foreign(['organization_id', 'department_id'], 'job_requisitions_org_department_fk')
                ->references(['organization_id', 'id'])
                ->on('hrms_departments')
                ->restrictOnDelete();
            $table->foreign(['organization_id', 'designation_id'], 'job_requisitions_org_designation_fk')
                ->references(['organization_id', 'id'])
                ->on('hrms_designations')
                ->restrictOnDelete();
            $table->foreign('hiring_manager_id', 'job_requisitions_hiring_manager_fk')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
            $table->index(['organization_id', 'status'], 'job_requisitions_org_status_idx');
        });

        Schema::create('job_openings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('job_requisition_id');
            $table->string('title');
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('designation_id');
            $table->string('employment_type', 30);
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->text('responsibilities')->nullable();
            $table->text('requirements')->nullable();
            $table->text('skills')->nullable();
            $table->decimal('salary_range_min', 15, 2)->nullable();
            $table->decimal('salary_range_max', 15, 2)->nullable();
            $table->string('experience')->nullable();
            $table->string('education')->nullable();
            $table->string('status', 30)->default('draft');
            $table->date('publish_date')->nullable();
            $table->date('closing_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'job_openings_org_id_unique');
            $table->foreign(['organization_id', 'job_requisition_id'], 'job_openings_org_requisition_fk')
                ->references(['organization_id', 'id'])
                ->on('job_requisitions')
                ->restrictOnDelete();
            $table->foreign(['organization_id', 'department_id'], 'job_openings_org_department_fk')
                ->references(['organization_id', 'id'])
                ->on('hrms_departments')
                ->restrictOnDelete();
            $table->foreign(['organization_id', 'designation_id'], 'job_openings_org_designation_fk')
                ->references(['organization_id', 'id'])
                ->on('hrms_designations')
                ->restrictOnDelete();
            $table->index(['organization_id', 'status'], 'job_openings_org_status_idx');
            $table->index(['organization_id', 'job_requisition_id'], 'job_openings_org_requisition_idx');
        });

        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('current_company')->nullable();
            $table->string('current_designation')->nullable();
            $table->string('experience')->nullable();
            $table->string('notice_period')->nullable();
            $table->decimal('current_salary', 15, 2)->nullable();
            $table->decimal('expected_salary', 15, 2)->nullable();
            $table->text('skills')->nullable();
            $table->string('resume_path')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('portfolio')->nullable();
            $table->string('source')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'candidates_org_id_unique');
            $table->unique(['organization_id', 'email'], 'candidates_org_email_unique');
            $table->index(['organization_id', 'source'], 'candidates_org_source_idx');
        });

        Schema::create('candidate_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('candidate_id');
            $table->string('category', 30);
            $table->string('title');
            $table->string('disk', 50);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'candidate_documents_org_id_unique');
            $table->foreign(['organization_id', 'candidate_id'], 'candidate_documents_org_candidate_fk')
                ->references(['organization_id', 'id'])
                ->on('candidates')
                ->cascadeOnDelete();
            $table->index(['organization_id', 'candidate_id'], 'candidate_documents_org_candidate_idx');
            $table->index(['organization_id', 'category'], 'candidate_documents_org_category_idx');
        });

        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('job_opening_id');
            $table->string('stage', 30)->default('applied');
            $table->string('status', 30)->default('active');
            $table->date('applied_date');
            $table->string('source')->nullable();
            $table->foreignId('assigned_recruiter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'job_applications_org_id_unique');
            $table->unique(['organization_id', 'candidate_id', 'job_opening_id'], 'job_applications_org_candidate_opening_unique');
            $table->foreign(['organization_id', 'candidate_id'], 'job_applications_org_candidate_fk')
                ->references(['organization_id', 'id'])
                ->on('candidates')
                ->restrictOnDelete();
            $table->foreign(['organization_id', 'job_opening_id'], 'job_applications_org_opening_fk')
                ->references(['organization_id', 'id'])
                ->on('job_openings')
                ->restrictOnDelete();
            $table->index(['organization_id', 'stage'], 'job_applications_org_stage_idx');
            $table->index(['organization_id', 'status'], 'job_applications_org_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('candidate_documents');
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('job_openings');
        Schema::dropIfExists('job_requisitions');
    }
};
