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
        Schema::create('expedition_rate_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expedition_id')->constrained()->cascadeOnDelete();
            $table->string('origin_depo')->index();
            $table->string('destination_city')->index();
            $table->string('destination_district')->nullable();
            $table->string('service_type')->default('REG');
            $table->decimal('rate_per_kg', 15, 2);
            $table->decimal('min_kg', 8, 2)->default(1);
            $table->decimal('insurance_rate_percent', 5, 2)->default(0.2); // e.g. 0.2%
            $table->string('sla_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['expedition_id', 'origin_depo', 'destination_city'], 'rate_card_lookup_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expedition_rate_cards');
    }
};
