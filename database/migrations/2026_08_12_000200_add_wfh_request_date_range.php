<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wfh_requests', function (Blueprint $table): void {
            $table->date('start_date')->nullable()->after('employee_id');
            $table->date('end_date')->nullable()->after('start_date');
        });

        DB::table('wfh_requests')->orderBy('id')->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                DB::table('wfh_requests')->where('id', $row->id)->update([
                    'start_date' => $row->work_date,
                    'end_date' => $row->work_date,
                ]);
            }
        });

        Schema::table('wfh_requests', function (Blueprint $table): void {
            $table->index(['organization_id', 'employee_id', 'start_date', 'end_date'], 'wfh_requests_org_employee_range_index');
        });
    }

    public function down(): void
    {
        Schema::table('wfh_requests', function (Blueprint $table): void {
            $table->dropIndex('wfh_requests_org_employee_range_index');
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
};
