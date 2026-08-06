<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_status', 32)->default('active')->after('remember_token');
            $table->boolean('portal_access_enabled')->default(true)->after('account_status');
            $table->timestamp('locked_at')->nullable()->after('portal_access_enabled');
            $table->timestamp('disabled_at')->nullable()->after('locked_at');
            $table->unsignedInteger('failed_login_attempts')->default(0)->after('disabled_at');
            $table->timestamp('last_login_at')->nullable()->after('failed_login_attempts');
            $table->timestamp('last_logout_at')->nullable()->after('last_login_at');
            $table->unsignedInteger('login_count')->default(0)->after('last_logout_at');
            $table->timestamp('password_changed_at')->nullable()->after('login_count');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'account_status',
                'portal_access_enabled',
                'locked_at',
                'disabled_at',
                'failed_login_attempts',
                'last_login_at',
                'last_logout_at',
                'login_count',
                'password_changed_at',
            ]);
        });
    }
};
