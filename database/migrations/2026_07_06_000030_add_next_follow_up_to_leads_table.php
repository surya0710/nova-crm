<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->timestamp('next_follow_up_at')->nullable()->after('status');
            $table->text('next_follow_up_note')->nullable()->after('next_follow_up_at');
            $table->timestamp('follow_up_alerted_at')->nullable()->after('next_follow_up_note');

            $table->index(['organization_id', 'next_follow_up_at']);
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'next_follow_up_at']);
            $table->dropColumn(['next_follow_up_at', 'next_follow_up_note', 'follow_up_alerted_at']);
        });
    }
};
