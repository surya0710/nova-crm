<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('payroll_run_id');
            $table->unsignedBigInteger('payroll_result_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('account_code', 40);
            $table->string('account_name', 120);
            $table->string('entry_type', 10); // debit|credit
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('description')->nullable();
            $table->boolean('is_reversal')->default(false);
            $table->unsignedBigInteger('reverses_entry_id')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'payroll_ledger_org_id_unique');
            $table->foreign('payroll_run_id', 'payroll_ledger_run_fk')
                ->references('id')->on('payroll_runs')->cascadeOnDelete();
            $table->foreign('payroll_result_id', 'payroll_ledger_result_fk')
                ->references('id')->on('payroll_results')->nullOnDelete();
            $table->foreign('employee_id', 'payroll_ledger_employee_fk')
                ->references('id')->on('employees')->nullOnDelete();
            $table->foreign('reverses_entry_id', 'payroll_ledger_reverses_fk')
                ->references('id')->on('payroll_ledger_entries')->nullOnDelete();
            $table->index(['organization_id', 'payroll_run_id'], 'payroll_ledger_org_run_idx');
            $table->index(['organization_id', 'account_code'], 'payroll_ledger_org_account_idx');
            $table->index(['organization_id', 'entry_type'], 'payroll_ledger_org_type_idx');
        });

        Schema::create('payroll_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('payroll_run_id');
            $table->string('journal_number', 60);
            $table->date('journal_date');
            $table->string('description')->nullable();
            $table->string('status', 30)->default('posted'); // posted|reversed
            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_credit', 15, 2)->default(0);
            $table->boolean('is_reversal')->default(false);
            $table->unsignedBigInteger('reverses_journal_id')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'payroll_journals_org_id_unique');
            $table->unique(['organization_id', 'journal_number'], 'payroll_journals_org_number_unique');
            $table->foreign('payroll_run_id', 'payroll_journals_run_fk')
                ->references('id')->on('payroll_runs')->cascadeOnDelete();
            $table->foreign('reverses_journal_id', 'payroll_journals_reverses_fk')
                ->references('id')->on('payroll_journals')->nullOnDelete();
            $table->index(['organization_id', 'payroll_run_id'], 'payroll_journals_org_run_idx');
            $table->index(['organization_id', 'journal_date'], 'payroll_journals_org_date_idx');
        });

        Schema::create('payroll_journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('payroll_journal_id');
            $table->unsignedBigInteger('payroll_ledger_entry_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('account_code', 40);
            $table->string('account_name', 120);
            $table->string('entry_type', 10);
            $table->decimal('amount', 15, 2);
            $table->string('description')->nullable();
            $table->unsignedInteger('line_order')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'payroll_journal_lines_org_id_unique');
            $table->foreign('payroll_journal_id', 'payroll_journal_lines_journal_fk')
                ->references('id')->on('payroll_journals')->cascadeOnDelete();
            $table->foreign('payroll_ledger_entry_id', 'payroll_journal_lines_ledger_fk')
                ->references('id')->on('payroll_ledger_entries')->nullOnDelete();
            $table->foreign('employee_id', 'payroll_journal_lines_employee_fk')
                ->references('id')->on('employees')->nullOnDelete();
            $table->index(['organization_id', 'payroll_journal_id'], 'payroll_journal_lines_org_journal_idx');
        });

        Schema::create('payroll_bank_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('payroll_run_id');
            $table->string('export_number', 60);
            $table->string('format', 20); // csv|xlsx
            $table->string('file_disk', 40)->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedInteger('employee_count')->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status', 30)->default('generated');
            $table->json('meta')->nullable();
            $table->foreignId('exported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('exported_at');
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'payroll_bank_exports_org_id_unique');
            $table->unique(['organization_id', 'export_number'], 'payroll_bank_exports_org_number_unique');
            $table->foreign('payroll_run_id', 'payroll_bank_exports_run_fk')
                ->references('id')->on('payroll_runs')->cascadeOnDelete();
            $table->index(['organization_id', 'payroll_run_id'], 'payroll_bank_exports_org_run_idx');
        });

        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->string('loan_number', 60);
            $table->string('loan_type', 40)->default('general');
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('outstanding_balance', 15, 2);
            $table->decimal('monthly_recovery', 15, 2);
            $table->decimal('interest_rate', 8, 4)->nullable();
            $table->date('disbursed_on')->nullable();
            $table->string('status', 30)->default('active'); // active|closed|cancelled
            $table->text('notes')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('closure_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'employee_loans_org_id_unique');
            $table->unique(['organization_id', 'loan_number'], 'employee_loans_org_number_unique');
            $table->foreign('employee_id', 'employee_loans_employee_fk')
                ->references('id')->on('employees')->restrictOnDelete();
            $table->index(['organization_id', 'employee_id'], 'employee_loans_org_employee_idx');
            $table->index(['organization_id', 'status'], 'employee_loans_org_status_idx');
        });

        Schema::create('employee_loan_recoveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_loan_id');
            $table->unsignedBigInteger('payroll_run_id')->nullable();
            $table->unsignedBigInteger('payroll_period_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('recovery_type', 30)->default('payroll'); // payroll|manual|closure
            $table->timestamp('recovered_at');
            $table->text('notes')->nullable();
            $table->foreignId('recovered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'employee_loan_recoveries_org_id_unique');
            $table->foreign('employee_loan_id', 'employee_loan_recoveries_loan_fk')
                ->references('id')->on('employee_loans')->cascadeOnDelete();
            $table->foreign('payroll_run_id', 'employee_loan_recoveries_run_fk')
                ->references('id')->on('payroll_runs')->nullOnDelete();
            $table->foreign('payroll_period_id', 'employee_loan_recoveries_period_fk')
                ->references('id')->on('payroll_periods')->nullOnDelete();
            $table->index(['organization_id', 'employee_loan_id'], 'employee_loan_recoveries_org_loan_idx');
        });

        Schema::create('salary_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->string('advance_number', 60);
            $table->decimal('amount', 15, 2);
            $table->decimal('outstanding_balance', 15, 2);
            $table->decimal('monthly_recovery', 15, 2);
            $table->string('status', 30)->default('pending'); // pending|approved|rejected|active|closed
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'salary_advances_org_id_unique');
            $table->unique(['organization_id', 'advance_number'], 'salary_advances_org_number_unique');
            $table->foreign('employee_id', 'salary_advances_employee_fk')
                ->references('id')->on('employees')->restrictOnDelete();
            $table->index(['organization_id', 'employee_id'], 'salary_advances_org_employee_idx');
            $table->index(['organization_id', 'status'], 'salary_advances_org_status_idx');
        });

        Schema::create('salary_advance_recoveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('salary_advance_id');
            $table->unsignedBigInteger('payroll_run_id')->nullable();
            $table->unsignedBigInteger('payroll_period_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('recovery_type', 30)->default('payroll');
            $table->timestamp('recovered_at');
            $table->text('notes')->nullable();
            $table->foreignId('recovered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'salary_advance_recoveries_org_id_unique');
            $table->foreign('salary_advance_id', 'salary_advance_recoveries_advance_fk')
                ->references('id')->on('salary_advances')->cascadeOnDelete();
            $table->foreign('payroll_run_id', 'salary_advance_recoveries_run_fk')
                ->references('id')->on('payroll_runs')->nullOnDelete();
            $table->foreign('payroll_period_id', 'salary_advance_recoveries_period_fk')
                ->references('id')->on('payroll_periods')->nullOnDelete();
            $table->index(['organization_id', 'salary_advance_id'], 'salary_advance_recoveries_org_advance_idx');
        });

        Schema::create('expense_reimbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->string('claim_number', 60);
            $table->string('category', 60)->default('general');
            $table->decimal('amount', 15, 2);
            $table->boolean('is_taxable')->default(false);
            $table->string('status', 30)->default('pending'); // pending|approved|rejected|included
            $table->text('description')->nullable();
            $table->unsignedBigInteger('payroll_run_id')->nullable();
            $table->timestamp('included_at')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'expense_reimbursements_org_id_unique');
            $table->unique(['organization_id', 'claim_number'], 'expense_reimbursements_org_number_unique');
            $table->foreign('employee_id', 'expense_reimbursements_employee_fk')
                ->references('id')->on('employees')->restrictOnDelete();
            $table->foreign('payroll_run_id', 'expense_reimbursements_run_fk')
                ->references('id')->on('payroll_runs')->nullOnDelete();
            $table->index(['organization_id', 'employee_id'], 'expense_reimbursements_org_employee_idx');
            $table->index(['organization_id', 'status'], 'expense_reimbursements_org_status_idx');
        });

        Schema::create('employee_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('employee_exit_process_id')->nullable();
            $table->string('settlement_number', 60);
            $table->string('status', 30)->default('draft'); // draft|completed|cancelled
            $table->decimal('pending_salary', 15, 2)->default(0);
            $table->decimal('leave_encashment', 15, 2)->default(0);
            $table->decimal('loan_recovery', 15, 2)->default(0);
            $table->decimal('advance_recovery', 15, 2)->default(0);
            $table->decimal('reimbursements', 15, 2)->default(0);
            $table->decimal('asset_deductions', 15, 2)->default(0);
            $table->decimal('statutory_deductions', 15, 2)->default(0);
            $table->decimal('net_settlement', 15, 2)->default(0);
            $table->json('statement')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'employee_settlements_org_id_unique');
            $table->unique(['organization_id', 'settlement_number'], 'employee_settlements_org_number_unique');
            $table->foreign('employee_id', 'employee_settlements_employee_fk')
                ->references('id')->on('employees')->restrictOnDelete();
            $table->foreign('employee_exit_process_id', 'employee_settlements_exit_fk')
                ->references('id')->on('employee_exit_processes')->nullOnDelete();
            $table->index(['organization_id', 'employee_id'], 'employee_settlements_org_employee_idx');
            $table->index(['organization_id', 'status'], 'employee_settlements_org_status_idx');
        });

        Schema::create('payroll_reversals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('payroll_run_id');
            $table->string('reversal_number', 60);
            $table->text('reason');
            $table->unsignedBigInteger('reversing_journal_id')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('reversed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('reversed_at');
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'payroll_reversals_org_id_unique');
            $table->unique(['organization_id', 'reversal_number'], 'payroll_reversals_org_number_unique');
            $table->unique(['organization_id', 'payroll_run_id'], 'payroll_reversals_org_run_unique');
            $table->foreign('payroll_run_id', 'payroll_reversals_run_fk')
                ->references('id')->on('payroll_runs')->cascadeOnDelete();
            $table->foreign('reversing_journal_id', 'payroll_reversals_journal_fk')
                ->references('id')->on('payroll_journals')->nullOnDelete();
            $table->index(['organization_id', 'reversed_at'], 'payroll_reversals_org_reversed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_reversals');
        Schema::dropIfExists('employee_settlements');
        Schema::dropIfExists('expense_reimbursements');
        Schema::dropIfExists('salary_advance_recoveries');
        Schema::dropIfExists('salary_advances');
        Schema::dropIfExists('employee_loan_recoveries');
        Schema::dropIfExists('employee_loans');
        Schema::dropIfExists('payroll_bank_exports');
        Schema::dropIfExists('payroll_journal_lines');
        Schema::dropIfExists('payroll_journals');
        Schema::dropIfExists('payroll_ledger_entries');
    }
};
