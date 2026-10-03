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
            $table->decimal('total_amount', 14, 2)->nullable()->after('weight');
            $table->string('store_name', 150)->nullable()->after('total_amount');
            $table->string('item_type', 150)->nullable()->after('store_name');
            $table->text('destination_address')->nullable()->after('item_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tariff_search_histories', function (Blueprint $table) {
            $table->dropColumn(['total_amount', 'store_name', 'item_type', 'destination_address']);
        });
    }
};
