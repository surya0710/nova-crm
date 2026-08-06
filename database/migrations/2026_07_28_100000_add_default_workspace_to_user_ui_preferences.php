<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_ui_preferences', function (Blueprint $table) {
            $table->string('default_workspace', 50)->nullable()->after('last_workspace');
        });
    }

    public function down(): void
    {
        Schema::table('user_ui_preferences', function (Blueprint $table) {
            $table->dropColumn('default_workspace');
        });
    }
};
