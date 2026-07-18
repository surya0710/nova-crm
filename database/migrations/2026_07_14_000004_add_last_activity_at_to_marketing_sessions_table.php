<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_sessions', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable()->after('ended_at');
        });

        DB::table('marketing_sessions')
            ->whereNull('last_activity_at')
            ->update(['last_activity_at' => DB::raw('started_at')]);
    }

    public function down(): void
    {
        Schema::table('marketing_sessions', function (Blueprint $table) {
            $table->dropColumn('last_activity_at');
        });
    }
};
