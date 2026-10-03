<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tariff_search_histories', function (Blueprint $table) {
            $table->json('rates_summary')->nullable()->after('cheapest_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tariff_search_histories', function (Blueprint $table) {
            $table->dropColumn('rates_summary');
        });
    }
};
