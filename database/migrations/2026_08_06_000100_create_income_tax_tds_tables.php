<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tax_financial_years')) {
            Schema::create('tax_financial_years', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->string('code', 20);
                $table->string('label');
                $table->string('assessment_year', 20);
                $table->date('start_date');
                $table->date('end_date');
                $table->string('default_regime', 20)->default('new');
                $table->boolean('is_active')->default(false);
                $table->unsignedInteger('version')->default(1);
                $table->json('configuration')->nullable();
                $table->json('custom_fields')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['organization_id', 'id'], 'tax_fy_org_id_unique');
                $table->unique(['organization_id', 'code', 'version'], 'tax_fy_org_code_version_unique');
                $table->index(['organization_id', 'is_active'], 'tax_fy_org_active_idx');
                $table->index(['organization_id', 'start_date', 'end_date'], 'tax_fy_org_dates_idx');
            });
        }

        if (! Schema::hasTable('tax_slabs')) {
            Schema::create('tax_slabs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('tax_financial_year_id');
                $table->string('regime', 20);
                $table->decimal('min_income', 15, 2)->default(0);
                $table->decimal('max_income', 15, 2)->nullable();
                $table->decimal('tax_percent', 8, 4)->default(0);
                $table->decimal('surcharge_percent', 8, 4)->default(0);
                $table->decimal('cess_percent', 8, 4)->default(4);
                $table->unsignedInteger('sort_order')->default(0);
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->unique(['organization_id', 'id'], 'tax_slabs_org_id_unique');
                $table->foreign('tax_financial_year_id', 'tax_slabs_fy_fk')
                    ->references('id')->on('tax_financial_years')->cascadeOnDelete();
                $table->index(['organization_id', 'tax_financial_year_id', 'regime'], 'tax_slabs_org_fy_regime_idx');
            });
        }

        if (! Schema::hasTable('employee_tax_regimes')) {
            Schema::create('employee_tax_regimes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('tax_financial_year_id');
                $table->string('regime', 20);
                $table->string('status', 30)->default('active');
                $table->date('effective_from');
                $table->date('effective_until')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('selected_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('selected_at')->nullable();
                $table->json('meta')->nullable();
                $table->json('custom_fields')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['organization_id', 'id'], 'emp_tax_regimes_org_id_unique');
                $table->foreign('employee_id', 'emp_tax_regimes_employee_fk')
                    ->references('id')->on('employees')->cascadeOnDelete();
                $table->foreign('tax_financial_year_id', 'emp_tax_regimes_fy_fk')
                    ->references('id')->on('tax_financial_years')->cascadeOnDelete();
                $table->index(['organization_id', 'employee_id', 'tax_financial_year_id'], 'emp_tax_regimes_org_emp_fy_idx');
            });
        }

        if (! Schema::hasTable('tax_projections')) {
            Schema::create('tax_projections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('tax_financial_year_id');
                $table->string('regime', 20);
                $table->decimal('projected_gross', 15, 2)->default(0);
                $table->decimal('projected_taxable', 15, 2)->default(0);
                $table->decimal('projected_tax', 15, 2)->default(0);
                $table->decimal('projected_cess', 15, 2)->default(0);
                $table->decimal('projected_surcharge', 15, 2)->default(0);
                $table->decimal('projected_rebate', 15, 2)->default(0);
                $table->decimal('annual_tax_liability', 15, 2)->default(0);
                $table->decimal('tds_already_deducted', 15, 2)->default(0);
                $table->decimal('remaining_tds', 15, 2)->default(0);
                $table->unsignedTinyInteger('remaining_months')->default(0);
                $table->decimal('monthly_tds', 15, 2)->default(0);
                $table->json('breakdown')->nullable();
                $table->string('source', 40)->default('system');
                $table->timestamp('calculated_at')->nullable();
                $table->json('custom_fields')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['organization_id', 'id'], 'tax_projections_org_id_unique');
                $table->unique(['organization_id', 'employee_id', 'tax_financial_year_id'], 'tax_projections_org_emp_fy_unique');
                $table->foreign('employee_id', 'tax_projections_employee_fk')
                    ->references('id')->on('employees')->cascadeOnDelete();
                $table->foreign('tax_financial_year_id', 'tax_projections_fy_fk')
                    ->references('id')->on('tax_financial_years')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('tax_declarations')) {
            Schema::create('tax_declarations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('tax_financial_year_id');
                $table->string('declaration_number', 60);
                $table->string('status', 30)->default('draft');
                $table->decimal('declared_total', 15, 2)->default(0);
                $table->decimal('approved_total', 15, 2)->default(0);
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('rejection_reason')->nullable();
                $table->text('verifier_comments')->nullable();
                $table->json('meta')->nullable();
                $table->json('custom_fields')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['organization_id', 'id'], 'tax_declarations_org_id_unique');
                $table->unique(['organization_id', 'declaration_number'], 'tax_declarations_org_number_unique');
                $table->foreign('employee_id', 'tax_declarations_employee_fk')
                    ->references('id')->on('employees')->cascadeOnDelete();
                $table->foreign('tax_financial_year_id', 'tax_declarations_fy_fk')
                    ->references('id')->on('tax_financial_years')->cascadeOnDelete();
                $table->index(['organization_id', 'employee_id', 'status'], 'tax_declarations_org_emp_status_idx');
                $table->index(['organization_id', 'tax_financial_year_id', 'status'], 'tax_declarations_org_fy_status_idx');
            });
        }

        if (! Schema::hasTable('tax_declaration_items')) {
            Schema::create('tax_declaration_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('tax_declaration_id');
                $table->string('category', 40);
                $table->string('section', 40)->nullable();
                $table->string('label');
                $table->decimal('declared_amount', 15, 2)->default(0);
                $table->decimal('approved_amount', 15, 2)->nullable();
                $table->string('status', 30)->default('draft');
                $table->text('notes')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->unique(['organization_id', 'id'], 'tax_declaration_items_org_id_unique');
                $table->foreign('tax_declaration_id', 'tax_declaration_items_decl_fk')
                    ->references('id')->on('tax_declarations')->cascadeOnDelete();
                $table->index(['organization_id', 'tax_declaration_id', 'category'], 'tax_declaration_items_org_decl_cat_idx');
            });
        }

        if (! Schema::hasTable('tax_proofs')) {
            Schema::create('tax_proofs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('tax_declaration_id');
                $table->unsignedBigInteger('tax_declaration_item_id')->nullable();
                $table->unsignedBigInteger('employee_id');
                $table->string('proof_number', 60);
                $table->string('category', 40);
                $table->string('title');
                $table->string('file_path')->nullable();
                $table->string('original_filename')->nullable();
                $table->string('mime_type', 120)->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->decimal('claimed_amount', 15, 2)->default(0);
                $table->decimal('approved_amount', 15, 2)->nullable();
                $table->string('status', 30)->default('uploaded');
                $table->text('comments')->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->nullable();
                $table->json('meta')->nullable();
                $table->json('custom_fields')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['organization_id', 'id'], 'tax_proofs_org_id_unique');
                $table->unique(['organization_id', 'proof_number'], 'tax_proofs_org_number_unique');
                $table->foreign('tax_declaration_id', 'tax_proofs_decl_fk')
                    ->references('id')->on('tax_declarations')->cascadeOnDelete();
                $table->foreign('tax_declaration_item_id', 'tax_proofs_item_fk')
                    ->references('id')->on('tax_declaration_items')->nullOnDelete();
                $table->foreign('employee_id', 'tax_proofs_employee_fk')
                    ->references('id')->on('employees')->cascadeOnDelete();
                $table->index(['organization_id', 'status'], 'tax_proofs_org_status_idx');
            });
        }

        if (! Schema::hasTable('tax_proof_audits')) {
            Schema::create('tax_proof_audits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('tax_proof_id');
                $table->string('action', 40);
                $table->string('from_status', 30)->nullable();
                $table->string('to_status', 30)->nullable();
                $table->decimal('approved_amount', 15, 2)->nullable();
                $table->text('comments')->nullable();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->foreign('tax_proof_id', 'tax_proof_audits_proof_fk')
                    ->references('id')->on('tax_proofs')->cascadeOnDelete();
                $table->index(['organization_id', 'tax_proof_id'], 'tax_proof_audits_org_proof_idx');
            });
        }

        if (! Schema::hasTable('tds_monthly_calculations')) {
            Schema::create('tds_monthly_calculations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('tax_financial_year_id');
                $table->unsignedBigInteger('payroll_period_id')->nullable();
                $table->unsignedBigInteger('payroll_run_id')->nullable();
                $table->unsignedBigInteger('tax_projection_id')->nullable();
                $table->unsignedTinyInteger('month');
                $table->unsignedSmallInteger('year');
                $table->string('regime', 20);
                $table->decimal('gross_salary', 15, 2)->default(0);
                $table->decimal('taxable_income_annual', 15, 2)->default(0);
                $table->decimal('annual_tax_liability', 15, 2)->default(0);
                $table->decimal('tds_ytd', 15, 2)->default(0);
                $table->decimal('tds_amount', 15, 2)->default(0);
                $table->decimal('cess_amount', 15, 2)->default(0);
                $table->decimal('surcharge_amount', 15, 2)->default(0);
                $table->decimal('rebate_amount', 15, 2)->default(0);
                $table->json('breakdown')->nullable();
                $table->string('status', 30)->default('calculated');
                $table->timestamp('calculated_at')->nullable();
                $table->json('custom_fields')->nullable();
                $table->timestamps();

                $table->unique(['organization_id', 'id'], 'tds_monthly_org_id_unique');
                $table->unique(['organization_id', 'employee_id', 'year', 'month'], 'tds_monthly_org_emp_ym_unique');
                $table->foreign('employee_id', 'tds_monthly_employee_fk')
                    ->references('id')->on('employees')->cascadeOnDelete();
                $table->foreign('tax_financial_year_id', 'tds_monthly_fy_fk')
                    ->references('id')->on('tax_financial_years')->cascadeOnDelete();
                $table->foreign('payroll_period_id', 'tds_monthly_period_fk')
                    ->references('id')->on('payroll_periods')->nullOnDelete();
                $table->foreign('payroll_run_id', 'tds_monthly_run_fk')
                    ->references('id')->on('payroll_runs')->nullOnDelete();
                $table->foreign('tax_projection_id', 'tds_monthly_projection_fk')
                    ->references('id')->on('tax_projections')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('form16_records')) {
            Schema::create('form16_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('tax_financial_year_id');
                $table->string('form_number', 60);
                $table->string('status', 30)->default('draft');
                $table->json('part_a')->nullable();
                $table->json('part_b')->nullable();
                $table->json('employer_details')->nullable();
                $table->json('employee_details')->nullable();
                $table->json('salary_breakup')->nullable();
                $table->json('deductions')->nullable();
                $table->json('tax_paid')->nullable();
                $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('generated_at')->nullable();
                $table->json('custom_fields')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['organization_id', 'id'], 'form16_org_id_unique');
                $table->unique(['organization_id', 'form_number'], 'form16_org_number_unique');
                $table->unique(['organization_id', 'employee_id', 'tax_financial_year_id'], 'form16_org_emp_fy_unique');
                $table->foreign('employee_id', 'form16_employee_fk')
                    ->references('id')->on('employees')->cascadeOnDelete();
                $table->foreign('tax_financial_year_id', 'form16_fy_fk')
                    ->references('id')->on('tax_financial_years')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('form16_records');
        Schema::dropIfExists('tds_monthly_calculations');
        Schema::dropIfExists('tax_proof_audits');
        Schema::dropIfExists('tax_proofs');
        Schema::dropIfExists('tax_declaration_items');
        Schema::dropIfExists('tax_declarations');
        Schema::dropIfExists('tax_projections');
        Schema::dropIfExists('employee_tax_regimes');
        Schema::dropIfExists('tax_slabs');
        Schema::dropIfExists('tax_financial_years');
    }
};
