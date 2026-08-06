<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendance_overtime_rules')) {
            Schema::create('attendance_overtime_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('code', 50)->nullable();
                $table->string('rule_type', 30);
                $table->unsignedInteger('minimum_minutes')->default(0);
                $table->unsignedInteger('maximum_minutes')->nullable();
                $table->unsignedSmallInteger('round_off_minutes')->default(0);
                $table->decimal('multiplier', 5, 2)->default(1);
                $table->boolean('requires_approval')->default(false);
                $table->boolean('is_active')->default(true);
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->unique(['organization_id', 'id'], 'attendance_overtime_rules_org_id_unique');
                $table->unique(['organization_id', 'code'], 'attendance_overtime_rules_org_code_unique');
                $table->index(['organization_id', 'rule_type', 'is_active'], 'attendance_overtime_rules_lookup_idx');
            });
        }

        if (! Schema::hasTable('attendance_overtime_entries')) {
            Schema::create('attendance_overtime_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('attendance_record_id')->nullable();
                $table->unsignedBigInteger('attendance_overtime_rule_id')->nullable();
                $table->date('attendance_date');
                $table->string('rule_type', 30);
                $table->unsignedInteger('minutes')->default(0);
                $table->decimal('amount', 15, 2)->nullable();
                $table->string('status', 30)->default('pending');
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamps();

                $table->unique(['organization_id', 'id'], 'attendance_overtime_entries_org_id_unique');
                $table->foreign(['organization_id', 'employee_id'], 'aoe_org_employee_fk')
                    ->references(['organization_id', 'id'])->on('employees')->cascadeOnDelete();
                $table->foreign('attendance_record_id', 'aoe_record_fk')
                    ->references('id')->on('attendance_records')->nullOnDelete();
                $table->foreign('attendance_overtime_rule_id', 'aoe_rule_fk')
                    ->references('id')->on('attendance_overtime_rules')->nullOnDelete();
                $table->index(['organization_id', 'attendance_date', 'status'], 'aoe_org_date_status_idx');
                $table->index(['organization_id', 'employee_id', 'attendance_date'], 'aoe_org_employee_date_idx');
                $table->index(['organization_id', 'attendance_overtime_rule_id'], 'aoe_org_rule_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_overtime_entries');
        Schema::dropIfExists('attendance_overtime_rules');
    }
};
