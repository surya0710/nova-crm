<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('payroll_run_id');
            $table->string('approval_type', 40)->default('hr');
            $table->foreignId('approved_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'payroll_approvals_org_id_unique');
            $table->foreign('payroll_run_id', 'payroll_approvals_run_fk')
                ->references('id')->on('payroll_runs')->cascadeOnDelete();
            $table->index(['organization_id', 'payroll_run_id'], 'payroll_approvals_org_run_idx');
            $table->index(['organization_id', 'approval_type'], 'payroll_approvals_org_type_idx');
        });

        Schema::create('payroll_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('payroll_run_id');
            $table->foreignId('published_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('published_at');
            $table->unsignedInteger('payslip_count')->default(0);
            $table->unsignedInteger('email_queued_count')->default(0);
            $table->string('status', 30)->default('published');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'payroll_publications_org_id_unique');
            $table->unique(['organization_id', 'payroll_run_id'], 'payroll_publications_org_run_unique');
            $table->foreign('payroll_run_id', 'payroll_publications_run_fk')
                ->references('id')->on('payroll_runs')->cascadeOnDelete();
            $table->index(['organization_id', 'published_at'], 'payroll_publications_org_published_idx');
        });

        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('payroll_run_id');
            $table->unsignedBigInteger('payroll_result_id');
            $table->unsignedBigInteger('payroll_publication_id')->nullable();
            $table->unsignedBigInteger('employee_id');
            $table->string('payslip_number', 60);
            $table->decimal('gross_salary', 15, 2)->default(0);
            $table->decimal('total_earnings', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('employer_contributions', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);
            $table->json('snapshot');
            $table->string('calculation_hash', 64);
            $table->string('pdf_disk', 40)->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('generated_at');
            $table->timestamp('emailed_at')->nullable();
            $table->unsignedInteger('email_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'payslips_org_id_unique');
            $table->unique(['organization_id', 'payslip_number'], 'payslips_org_number_unique');
            $table->unique(['organization_id', 'payroll_result_id'], 'payslips_org_result_unique');
            $table->foreign('payroll_run_id', 'payslips_run_fk')
                ->references('id')->on('payroll_runs')->cascadeOnDelete();
            $table->foreign('payroll_result_id', 'payslips_result_fk')
                ->references('id')->on('payroll_results')->restrictOnDelete();
            $table->foreign('payroll_publication_id', 'payslips_publication_fk')
                ->references('id')->on('payroll_publications')->nullOnDelete();
            $table->foreign('employee_id', 'payslips_employee_fk')
                ->references('id')->on('employees')->restrictOnDelete();
            $table->index(['organization_id', 'employee_id'], 'payslips_org_employee_idx');
            $table->index(['organization_id', 'payroll_run_id'], 'payslips_org_run_idx');
            $table->index(['organization_id', 'generated_at'], 'payslips_org_generated_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_publications');
        Schema::dropIfExists('payroll_approvals');
    }
};
