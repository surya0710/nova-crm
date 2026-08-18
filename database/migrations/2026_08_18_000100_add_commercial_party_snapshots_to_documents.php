<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->json('billing_snapshot')->nullable()->after('place_of_supply');
            $table->json('shipping_snapshot')->nullable()->after('billing_snapshot');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->json('billing_snapshot')->nullable()->after('place_of_supply');
            $table->json('shipping_snapshot')->nullable()->after('billing_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn(['billing_snapshot', 'shipping_snapshot']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['billing_snapshot', 'shipping_snapshot']);
        });
    }
};
