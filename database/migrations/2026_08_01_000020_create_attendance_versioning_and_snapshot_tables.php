<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            if (! Schema::hasColumn('attendance_records', 'version')) {
                $table->unsignedInteger('version')->default(1)->after('notes');
            }
            if (! Schema::hasColumn('attendance_records', 'approval_status')) {
                $table->string('approval_status', 30)->default('approved')->after('version');
            }
            if (! Schema::hasColumn('attendance_records', 'break_minutes')) {
                $table->unsignedInteger('break_minutes')->default(0)->after('working_minutes');
            }
            if (! Schema::hasColumn('attendance_records', 'locked_at')) {
                $table->timestamp('locked_at')->nullable()->after('approval_status');
            }
            if (! Schema::hasColumn('attendance_records', 'locked_by')) {
                $table->foreignId('locked_by')->nullable()->after('locked_at')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('attendance_corrections', function (Blueprint $table) {
            if (! Schema::hasColumn('attendance_corrections', 'target_version')) {
                $table->unsignedInteger('target_version')->nullable()->after('status');
            }
            if (! Schema::hasColumn('attendance_corrections', 'resulting_version')) {
                $table->unsignedInteger('resulting_version')->nullable()->after('target_version');
            }
            if (! Schema::hasColumn('attendance_corrections', 'current_step')) {
                $table->string('current_step', 50)->nullable()->after('resulting_version');
            }
            if (! Schema::hasColumn('attendance_corrections', 'requires_hr_approval')) {
                $table->boolean('requires_hr_approval')->default(false)->after('current_step');
            }
        });

        Schema::table('hrms_shifts', function (Blueprint $table) {
            if (! Schema::hasColumn('hrms_shifts', 'late_threshold_minutes')) {
                $table->unsignedSmallInteger('late_threshold_minutes')->nullable()->after('grace_period_minutes');
            }
            if (! Schema::hasColumn('hrms_shifts', 'early_exit_threshold_minutes')) {
                $table->unsignedSmallInteger('early_exit_threshold_minutes')->nullable()->after('late_threshold_minutes');
            }
            if (! Schema::hasColumn('hrms_shifts', 'maximum_working_minutes')) {
                $table->unsignedSmallInteger('maximum_working_minutes')->nullable()->after('overtime_threshold_minutes');
            }
            if (! Schema::hasColumn('hrms_shifts', 'overtime_allowed')) {
                $table->boolean('overtime_allowed')->default(true)->after('maximum_working_minutes');
            }
        });

        if (! Schema::hasTable('attendance_record_versions')) {
            Schema::create('attendance_record_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('attendance_record_id');
                $table->unsignedInteger('version');
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('shift_id')->nullable();
                $table->date('attendance_date');
                $table->timestamp('clock_in_at')->nullable();
                $table->timestamp('clock_out_at')->nullable();
                $table->string('status', 30);
                $table->string('approval_status', 30)->default('approved');
                $table->string('source', 30)->default('manual');
                $table->unsignedInteger('working_minutes')->default(0);
                $table->unsignedInteger('break_minutes')->default(0);
                $table->unsignedInteger('late_minutes')->default(0);
                $table->unsignedInteger('early_departure_minutes')->default(0);
                $table->unsignedInteger('overtime_minutes')->default(0);
                $table->text('notes')->nullable();
                $table->string('change_reason')->nullable();
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->json('payload')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['organization_id', 'id'], 'arv_org_id_unique');
                $table->unique(['attendance_record_id', 'version'], 'arv_record_version_unique');
                $table->foreign(['organization_id', 'attendance_record_id'], 'arv_org_record_fk')
                    ->references(['organization_id', 'id'])->on('attendance_records')->cascadeOnDelete();
                $table->foreign(['organization_id', 'employee_id'], 'arv_org_employee_fk')
                    ->references(['organization_id', 'id'])->on('employees')->cascadeOnDelete();
                $table->index(['organization_id', 'attendance_record_id'], 'arv_org_record_idx');
                $table->index(['organization_id', 'employee_id', 'attendance_date'], 'arv_org_employee_date_idx');
            });
        }

        if (! Schema::hasTable('attendance_periods')) {
            Schema::create('attendance_periods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->date('start_date');
                $table->date('end_date');
                $table->string('status', 30)->default('open');
                $table->unsignedBigInteger('payroll_period_id')->nullable();
                $table->timestamp('frozen_at')->nullable();
                $table->foreignId('frozen_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('locked_at')->nullable();
                $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reopened_at')->nullable();
                $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['organization_id', 'id'], 'attendance_periods_org_id_unique');
                $table->foreign('payroll_period_id', 'attendance_periods_payroll_period_fk')
                    ->references('id')->on('payroll_periods')->nullOnDelete();
                $table->index(['organization_id', 'status'], 'attendance_periods_org_status_idx');
                $table->index(['organization_id', 'start_date', 'end_date'], 'attendance_periods_org_range_idx');
                $table->unique(['organization_id', 'start_date', 'end_date'], 'attendance_periods_org_range_unique');
            });
        }

        if (! Schema::hasTable('attendance_snapshots')) {
            Schema::create('attendance_snapshots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('attendance_period_id');
                $table->unsignedInteger('snapshot_version')->default(1);
                $table->string('status', 30)->default('active');
                $table->string('payload_hash', 64);
                $table->unsignedInteger('record_count')->default(0);
                $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('generated_at');
                $table->timestamp('superseded_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->unique(['organization_id', 'id'], 'attendance_snapshots_org_id_unique');
                $table->unique(['attendance_period_id', 'snapshot_version'], 'attendance_snapshots_period_version_unique');
                $table->foreign(['organization_id', 'attendance_period_id'], 'attendance_snapshots_org_period_fk')
                    ->references(['organization_id', 'id'])->on('attendance_periods')->cascadeOnDelete();
                $table->index(['organization_id', 'attendance_period_id', 'status'], 'attendance_snapshots_lookup_idx');
            });
        }

        if (! Schema::hasTable('attendance_snapshot_rows')) {
            Schema::create('attendance_snapshot_rows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('attendance_snapshot_id');
                $table->unsignedBigInteger('attendance_record_id')->nullable();
                $table->unsignedBigInteger('employee_id');
                $table->date('attendance_date');
                $table->unsignedInteger('attendance_record_version')->default(1);
                $table->string('status', 30);
                $table->unsignedInteger('working_minutes')->default(0);
                $table->unsignedInteger('break_minutes')->default(0);
                $table->unsignedInteger('late_minutes')->default(0);
                $table->unsignedInteger('early_departure_minutes')->default(0);
                $table->unsignedInteger('overtime_minutes')->default(0);
                $table->json('leave_context')->nullable();
                $table->json('payload')->nullable();
                $table->string('payload_hash', 64);
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['organization_id', 'id'], 'asr_org_id_unique');
                $table->foreign(['organization_id', 'attendance_snapshot_id'], 'asr_org_snapshot_fk')
                    ->references(['organization_id', 'id'])->on('attendance_snapshots')->cascadeOnDelete();
                $table->foreign(['organization_id', 'employee_id'], 'asr_org_employee_fk')
                    ->references(['organization_id', 'id'])->on('employees')->cascadeOnDelete();
                $table->index(['organization_id', 'attendance_snapshot_id', 'employee_id'], 'asr_snapshot_employee_idx');
                $table->index(['organization_id', 'employee_id', 'attendance_date'], 'asr_employee_date_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_snapshot_rows');
        Schema::dropIfExists('attendance_snapshots');
        Schema::dropIfExists('attendance_periods');
        Schema::dropIfExists('attendance_record_versions');

        Schema::table('hrms_shifts', function (Blueprint $table) {
            foreach (['late_threshold_minutes', 'early_exit_threshold_minutes', 'maximum_working_minutes', 'overtime_allowed'] as $column) {
                if (Schema::hasColumn('hrms_shifts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('attendance_corrections', function (Blueprint $table) {
            foreach (['target_version', 'resulting_version', 'current_step', 'requires_hr_approval'] as $column) {
                if (Schema::hasColumn('attendance_corrections', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_records', 'locked_by')) {
                $table->dropConstrainedForeignId('locked_by');
            }
            foreach (['version', 'approval_status', 'break_minutes', 'locked_at'] as $column) {
                if (Schema::hasColumn('attendance_records', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
