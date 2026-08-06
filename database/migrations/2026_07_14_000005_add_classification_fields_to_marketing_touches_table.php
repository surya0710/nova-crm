<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_touches', function (Blueprint $table) {
            $table->string('gclid')->nullable()->after('term');
            $table->string('fbclid')->nullable()->after('gclid');
            $table->string('msclkid')->nullable()->after('fbclid');
            $table->string('referrer_host')->nullable()->after('referrer');

            // Selective click-ID indexes per the TDS: future provider phases
            // resolve click IDs back to campaigns via these lookups.
            $table->index('gclid');
            $table->index('fbclid');
            $table->index('msclkid');
        });
    }

    public function down(): void
    {
        Schema::table('marketing_touches', function (Blueprint $table) {
            $table->dropIndex(['gclid']);
            $table->dropIndex(['fbclid']);
            $table->dropIndex(['msclkid']);
            $table->dropColumn(['gclid', 'fbclid', 'msclkid', 'referrer_host']);
        });
    }
};
