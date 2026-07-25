<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->unsignedSmallInteger('allocation_days')->nullable()->after('max_days_per_year');
            $table->boolean('carry_forward_allowed')->default(false)->after('allocation_days');
            $table->boolean('negative_balance_allowed')->default(false)->after('carry_forward_allowed');
            $table->unsignedSmallInteger('max_consecutive_days')->nullable()->after('negative_balance_allowed');
        });

        Schema::table('holidays', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('organization_id');
            $table->boolean('is_recurring')->default(false)->after('is_optional');

            $table->foreign('branch_id', 'holidays_branch_fk')
                ->references('id')->on('hrms_branches')->nullOnDelete();
        });

        Schema::table('holidays', function (Blueprint $table) {
            $table->dropUnique('holidays_org_date_unique');
            $table->unique(['organization_id', 'holiday_date', 'branch_id'], 'holidays_org_date_branch_unique');
        });

        Schema::create('leave_balance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('leave_balance_id');
            $table->string('transaction_type', 50);
            $table->decimal('quantity', 8, 2);
            $table->decimal('balance_before', 8, 2);
            $table->decimal('balance_after', 8, 2);
            $table->text('remarks')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'leave_balance_tx_org_id_unique');
            $table->foreign(['organization_id', 'leave_balance_id'], 'leave_balance_tx_org_balance_fk')
                ->references(['organization_id', 'id'])->on('leave_balances')->cascadeOnDelete();
            $table->index(['organization_id', 'leave_balance_id'], 'leave_balance_tx_org_balance_idx');
            $table->index(['reference_type', 'reference_id'], 'leave_balance_tx_reference_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balance_transactions');

        Schema::table('holidays', function (Blueprint $table) {
            $table->dropUnique('holidays_org_date_branch_unique');
            $table->dropForeign('holidays_branch_fk');
            $table->dropColumn(['branch_id', 'is_recurring']);
            $table->unique(['organization_id', 'holiday_date'], 'holidays_org_date_unique');
        });

        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn([
                'allocation_days',
                'carry_forward_allowed',
                'negative_balance_allowed',
                'max_consecutive_days',
            ]);
        });
    }
};
