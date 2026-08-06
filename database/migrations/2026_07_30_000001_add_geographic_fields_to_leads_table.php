<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('address_line_1')->nullable()->after('industry');
            $table->string('city')->nullable()->after('address_line_1');
            $table->string('state')->nullable()->after('city');
            $table->string('country')->nullable()->after('state');
            $table->string('postal_code', 20)->nullable()->after('country');

            $table->index(['organization_id', 'state'], 'leads_org_state_index');
            $table->index(['organization_id', 'country'], 'leads_org_country_index');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index(['organization_id', 'state'], 'customers_org_state_index');
            $table->index(['organization_id', 'country'], 'customers_org_country_index');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_org_state_index');
            $table->dropIndex('customers_org_country_index');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex('leads_org_state_index');
            $table->dropIndex('leads_org_country_index');
            $table->dropColumn(['address_line_1', 'city', 'state', 'country', 'postal_code']);
        });
    }
};
