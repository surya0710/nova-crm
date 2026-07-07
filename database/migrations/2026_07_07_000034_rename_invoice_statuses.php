<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('invoices')->where('status', 'sent')->update(['status' => 'issued']);
        DB::table('invoices')->where('status', 'partial')->update(['status' => 'partially_paid']);
        DB::table('invoices')->where('status', 'overdue')->update(['status' => 'issued']);
    }

    public function down(): void
    {
        DB::table('invoices')->where('status', 'issued')->update(['status' => 'sent']);
        DB::table('invoices')->where('status', 'partially_paid')->update(['status' => 'partial']);
    }
};
