<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrms_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code', 30)->nullable();
            $table->string('country', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'hrms_branches_org_id_unique');
            $table->unique(['organization_id', 'code'], 'hrms_branches_org_code_unique');
            $table->index(['organization_id', 'is_active'], 'hrms_branches_org_active_idx');
        });

        Schema::create('hrms_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'hrms_departments_org_id_unique');
            $table->unique(['organization_id', 'code'], 'hrms_departments_org_code_unique');
            $table->foreign('branch_id', 'hrms_departments_branch_fk')
                ->references('id')->on('hrms_branches')->nullOnDelete();
            $table->foreign('parent_id', 'hrms_departments_parent_fk')
                ->references('id')->on('hrms_departments')->nullOnDelete();
            $table->index(['organization_id', 'is_active'], 'hrms_departments_org_active_idx');
        });

        Schema::create('hrms_designations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->unsignedSmallInteger('level')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'hrms_designations_org_id_unique');
            $table->unique(['organization_id', 'code'], 'hrms_designations_org_code_unique');
            $table->index(['organization_id', 'is_active'], 'hrms_designations_org_active_idx');
        });

        Schema::create('hrms_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'hrms_teams_org_id_unique');
            $table->unique(['organization_id', 'code'], 'hrms_teams_org_code_unique');
            $table->foreign('department_id', 'hrms_teams_department_fk')
                ->references('id')->on('hrms_departments')->nullOnDelete();
            $table->index(['organization_id', 'is_active'], 'hrms_teams_org_active_idx');
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('employee_code', 50);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->unsignedBigInteger('reporting_manager_id')->nullable();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('employment_type', 50)->default('full_time');
            $table->string('status', 50)->default('draft');
            $table->date('joining_date')->nullable();
            $table->date('probation_end_date')->nullable();
            $table->date('exit_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'employees_org_id_unique');
            $table->unique(['organization_id', 'employee_code'], 'employees_org_code_unique');
            $table->unique(['organization_id', 'user_id'], 'employees_org_user_unique');
            $table->foreign('branch_id', 'employees_branch_fk')
                ->references('id')->on('hrms_branches')->nullOnDelete();
            $table->foreign('department_id', 'employees_department_fk')
                ->references('id')->on('hrms_departments')->nullOnDelete();
            $table->foreign('designation_id', 'employees_designation_fk')
                ->references('id')->on('hrms_designations')->nullOnDelete();
            $table->foreign('reporting_manager_id', 'employees_manager_fk')
                ->references('id')->on('employees')->nullOnDelete();
            $table->index(['organization_id', 'status'], 'employees_org_status_idx');
            $table->index(['organization_id', 'department_id'], 'employees_org_department_idx');
        });

        Schema::create('employee_emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->string('name');
            $table->string('relationship')->nullable();
            $table->string('phone', 50);
            $table->string('email')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'employee_emergency_contacts_org_id_unique');
            $table->foreign(['organization_id', 'employee_id'], 'employee_emergency_contacts_org_employee_fk')
                ->references(['organization_id', 'id'])->on('employees')->cascadeOnDelete();
        });

        Schema::create('employee_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->string('bank_name');
            $table->string('account_holder_name');
            $table->string('account_number');
            $table->string('ifsc_or_swift', 50)->nullable();
            $table->string('branch_name')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'employee_bank_accounts_org_id_unique');
            $table->foreign(['organization_id', 'employee_id'], 'employee_bank_accounts_org_employee_fk')
                ->references(['organization_id', 'id'])->on('employees')->cascadeOnDelete();
        });

        Schema::create('employee_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->string('type', 50);
            $table->string('number');
            $table->date('issued_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'employee_identities_org_id_unique');
            $table->foreign(['organization_id', 'employee_id'], 'employee_identities_org_employee_fk')
                ->references(['organization_id', 'id'])->on('employees')->cascadeOnDelete();
            $table->unique(['organization_id', 'employee_id', 'type'], 'employee_identities_org_employee_type_unique');
        });

        Schema::create('employee_educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->string('institution');
            $table->string('degree')->nullable();
            $table->string('field_of_study')->nullable();
            $table->unsignedSmallInteger('start_year')->nullable();
            $table->unsignedSmallInteger('end_year')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'employee_educations_org_id_unique');
            $table->foreign(['organization_id', 'employee_id'], 'employee_educations_org_employee_fk')
                ->references(['organization_id', 'id'])->on('employees')->cascadeOnDelete();
        });

        Schema::create('employee_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->string('company');
            $table->string('title')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'employee_experiences_org_id_unique');
            $table->foreign(['organization_id', 'employee_id'], 'employee_experiences_org_employee_fk')
                ->references(['organization_id', 'id'])->on('employees')->cascadeOnDelete();
        });

        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->string('category', 50);
            $table->string('title');
            $table->date('expires_at')->nullable();
            $table->string('verification_status', 30)->default('pending');
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'employee_documents_org_id_unique');
            $table->foreign(['organization_id', 'employee_id'], 'employee_documents_org_employee_fk')
                ->references(['organization_id', 'id'])->on('employees')->cascadeOnDelete();
            $table->index(['organization_id', 'category'], 'employee_documents_org_category_idx');
            $table->index(['organization_id', 'expires_at'], 'employee_documents_org_expires_idx');
        });

        Schema::create('employee_document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_document_id');
            $table->unsignedInteger('version_no');
            $table->string('disk', 50)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'employee_document_versions_org_id_unique');
            $table->unique(['organization_id', 'employee_document_id', 'version_no'], 'employee_document_versions_org_doc_ver_unique');
            $table->foreign(['organization_id', 'employee_document_id'], 'employee_document_versions_org_doc_fk')
                ->references(['organization_id', 'id'])->on('employee_documents')->cascadeOnDelete();
        });

        Schema::table('employee_documents', function (Blueprint $table) {
            $table->foreign('current_version_id', 'employee_documents_current_version_fk')
                ->references('id')->on('employee_document_versions')->nullOnDelete();
        });

        Schema::create('hrms_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('break_minutes')->default(0);
            $table->decimal('working_hours', 5, 2)->nullable();
            $table->boolean('is_overnight')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'hrms_shifts_org_id_unique');
            $table->unique(['organization_id', 'code'], 'hrms_shifts_org_code_unique');
            $table->index(['organization_id', 'is_active'], 'hrms_shifts_org_active_idx');
        });

        Schema::create('employee_shift_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('shift_id');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'employee_shift_assignments_org_id_unique');
            $table->foreign(['organization_id', 'employee_id'], 'employee_shift_assignments_org_employee_fk')
                ->references(['organization_id', 'id'])->on('employees')->cascadeOnDelete();
            $table->foreign(['organization_id', 'shift_id'], 'employee_shift_assignments_org_shift_fk')
                ->references(['organization_id', 'id'])->on('hrms_shifts')->cascadeOnDelete();
            $table->index(['organization_id', 'employee_id', 'effective_from'], 'employee_shift_assignments_lookup_idx');
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->date('attendance_date');
            $table->timestamp('clock_in_at')->nullable();
            $table->timestamp('clock_out_at')->nullable();
            $table->string('status', 50)->default('pending');
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('early_departure_minutes')->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'attendance_records_org_id_unique');
            $table->unique(['organization_id', 'employee_id', 'attendance_date'], 'attendance_records_org_employee_date_unique');
            $table->foreign(['organization_id', 'employee_id'], 'attendance_records_org_employee_fk')
                ->references(['organization_id', 'id'])->on('employees')->cascadeOnDelete();
            $table->foreign('shift_id', 'attendance_records_shift_fk')
                ->references('id')->on('hrms_shifts')->nullOnDelete();
            $table->index(['organization_id', 'attendance_date'], 'attendance_records_org_date_idx');
        });

        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('attendance_record_id');
            $table->unsignedBigInteger('employee_id');
            $table->timestamp('requested_clock_in_at')->nullable();
            $table->timestamp('requested_clock_out_at')->nullable();
            $table->text('reason');
            $table->string('status', 30)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'attendance_corrections_org_id_unique');
            $table->foreign(['organization_id', 'attendance_record_id'], 'attendance_corrections_org_record_fk')
                ->references(['organization_id', 'id'])->on('attendance_records')->cascadeOnDelete();
            $table->foreign(['organization_id', 'employee_id'], 'attendance_corrections_org_employee_fk')
                ->references(['organization_id', 'id'])->on('employees')->cascadeOnDelete();
            $table->index(['organization_id', 'status'], 'attendance_corrections_org_status_idx');
        });

        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50);
            $table->boolean('is_paid')->default(true);
            $table->boolean('requires_approval')->default(true);
            $table->boolean('requires_hr_approval')->default(false);
            $table->boolean('allow_half_day')->default(true);
            $table->unsignedSmallInteger('max_days_per_year')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'leave_types_org_id_unique');
            $table->unique(['organization_id', 'code'], 'leave_types_org_code_unique');
            $table->index(['organization_id', 'is_active'], 'leave_types_org_active_idx');
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('holiday_date');
            $table->boolean('is_optional')->default(false);
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'holidays_org_id_unique');
            $table->unique(['organization_id', 'holiday_date'], 'holidays_org_date_unique');
            $table->index(['organization_id', 'holiday_date'], 'holidays_org_date_idx');
        });

        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('leave_type_id');
            $table->unsignedSmallInteger('year');
            $table->decimal('entitled', 8, 2)->default(0);
            $table->decimal('used', 8, 2)->default(0);
            $table->decimal('pending', 8, 2)->default(0);
            $table->decimal('balance', 8, 2)->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'leave_balances_org_id_unique');
            $table->unique(['organization_id', 'employee_id', 'leave_type_id', 'year'], 'leave_balances_org_employee_type_year_unique');
            $table->foreign(['organization_id', 'employee_id'], 'leave_balances_org_employee_fk')
                ->references(['organization_id', 'id'])->on('employees')->cascadeOnDelete();
            $table->foreign(['organization_id', 'leave_type_id'], 'leave_balances_org_leave_type_fk')
                ->references(['organization_id', 'id'])->on('leave_types')->cascadeOnDelete();
        });

        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('leave_type_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_half_day')->default(false);
            $table->string('half_day_period', 30)->nullable();
            $table->decimal('days', 8, 2)->default(1);
            $table->text('reason')->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'leave_applications_org_id_unique');
            $table->foreign(['organization_id', 'employee_id'], 'leave_applications_org_employee_fk')
                ->references(['organization_id', 'id'])->on('employees')->cascadeOnDelete();
            $table->foreign(['organization_id', 'leave_type_id'], 'leave_applications_org_leave_type_fk')
                ->references(['organization_id', 'id'])->on('leave_types')->cascadeOnDelete();
            $table->index(['organization_id', 'status'], 'leave_applications_org_status_idx');
            $table->index(['organization_id', 'start_date', 'end_date'], 'leave_applications_org_dates_idx');
        });

        Schema::create('leave_approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('leave_application_id');
            $table->unsignedSmallInteger('step_order');
            $table->unsignedBigInteger('approver_employee_id')->nullable();
            $table->foreignId('approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('pending');
            $table->timestamp('acted_at')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'leave_approval_steps_org_id_unique');
            $table->unique(['organization_id', 'leave_application_id', 'step_order'], 'leave_approval_steps_org_app_step_unique');
            $table->foreign(['organization_id', 'leave_application_id'], 'leave_approval_steps_org_application_fk')
                ->references(['organization_id', 'id'])->on('leave_applications')->cascadeOnDelete();
            $table->foreign('approver_employee_id', 'leave_approval_steps_approver_employee_fk')
                ->references('id')->on('employees')->nullOnDelete();
            $table->index(['organization_id', 'status'], 'leave_approval_steps_org_status_idx');
        });

        Schema::create('hrms_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'hrms_announcements_org_id_unique');
            $table->index(['organization_id', 'is_active', 'published_at'], 'hrms_announcements_org_active_published_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrms_announcements');
        Schema::dropIfExists('leave_approval_steps');
        Schema::dropIfExists('leave_applications');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('attendance_corrections');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('employee_shift_assignments');
        Schema::dropIfExists('hrms_shifts');
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->dropForeign('employee_documents_current_version_fk');
        });
        Schema::dropIfExists('employee_document_versions');
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('employee_experiences');
        Schema::dropIfExists('employee_educations');
        Schema::dropIfExists('employee_identities');
        Schema::dropIfExists('employee_bank_accounts');
        Schema::dropIfExists('employee_emergency_contacts');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('hrms_teams');
        Schema::dropIfExists('hrms_designations');
        Schema::dropIfExists('hrms_departments');
        Schema::dropIfExists('hrms_branches');
    }
};
