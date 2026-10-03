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
        Schema::table('expedition_rate_cards', function (Blueprint $table) {
            $table->string('province')->nullable()->after('destination_district');
            $table->text('notes')->nullable()->after('sla_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expedition_rate_cards', function (Blueprint $table) {
            $table->dropColumn(['province', 'notes']);
        });
    }
};
