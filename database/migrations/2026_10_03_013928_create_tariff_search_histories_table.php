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
        Schema::create('tariff_search_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('origin', 100);
            $table->string('destination', 100);
            $table->decimal('weight', 8, 2)->default(1);
            $table->string('service', 50)->nullable();
            $table->unsignedInteger('total_results')->default(0);
            $table->string('cheapest_expedition', 100)->nullable();
            $table->decimal('cheapest_cost', 12, 2)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['origin', 'destination']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tariff_search_histories');
    }
};
