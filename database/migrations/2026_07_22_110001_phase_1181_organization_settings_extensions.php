<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrms_branches', function (Blueprint $table) {
            if (! Schema::hasColumn('hrms_branches', 'manager_employee_id')) {
                $table->unsignedBigInteger('manager_employee_id')->nullable()->after('contact_phone');
                $table->foreign('manager_employee_id', 'hrms_branches_manager_fk')
                    ->references('id')->on('employees')->nullOnDelete();
            }
            if (! Schema::hasColumn('hrms_branches', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('is_active');
                $table->index(['organization_id', 'is_default'], 'hrms_branches_org_default_idx');
            }
        });

        Schema::table('hrms_shifts', function (Blueprint $table) {
            if (! Schema::hasColumn('hrms_shifts', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('is_active');
                $table->index(['organization_id', 'is_default'], 'hrms_shifts_org_default_idx');
            }
        });

        if (Schema::hasTable('interview_rounds')) {
            Schema::table('interview_rounds', function (Blueprint $table) {
                if (! Schema::hasColumn('interview_rounds', 'meeting_id')) {
                    $table->string('meeting_id', 191)->nullable()->after('meeting_provider');
                }
                if (! Schema::hasColumn('interview_rounds', 'join_instructions')) {
                    $table->text('join_instructions')->nullable()->after('meeting_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('interview_rounds')) {
            Schema::table('interview_rounds', function (Blueprint $table) {
                if (Schema::hasColumn('interview_rounds', 'join_instructions')) {
                    $table->dropColumn('join_instructions');
                }
                if (Schema::hasColumn('interview_rounds', 'meeting_id')) {
                    $table->dropColumn('meeting_id');
                }
            });
        }

        Schema::table('hrms_shifts', function (Blueprint $table) {
            if (Schema::hasColumn('hrms_shifts', 'is_default')) {
                $table->dropIndex('hrms_shifts_org_default_idx');
                $table->dropColumn('is_default');
            }
        });

        Schema::table('hrms_branches', function (Blueprint $table) {
            if (Schema::hasColumn('hrms_branches', 'manager_employee_id')) {
                $table->dropForeign('hrms_branches_manager_fk');
                $table->dropColumn('manager_employee_id');
            }
            if (Schema::hasColumn('hrms_branches', 'is_default')) {
                $table->dropIndex('hrms_branches_org_default_idx');
                $table->dropColumn('is_default');
            }
        });
    }
};
