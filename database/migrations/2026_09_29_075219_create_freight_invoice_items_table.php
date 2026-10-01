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
        Schema::create('freight_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('freight_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('csa_shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('no_resi_awb')->index();
            $table->string('no_sj')->nullable()->index();
            $table->string('origin_depo')->nullable();
            $table->string('destination_city')->nullable();
            $table->decimal('billed_weight', 8, 2)->default(0);
            $table->decimal('actual_weight', 8, 2)->nullable();
            $table->decimal('weight_discrepancy', 8, 2)->default(0);
            $table->decimal('billed_rate', 15, 2)->default(0);
            $table->decimal('agreed_rate', 15, 2)->default(0);
            $table->decimal('billed_insurance', 15, 2)->default(0);
            $table->decimal('agreed_insurance', 15, 2)->default(0);
            $table->decimal('billed_total', 15, 2)->default(0);
            $table->decimal('expected_total', 15, 2)->default(0);
            $table->decimal('discrepancy_amount', 15, 2)->default(0);
            $table->string('audit_status')->default('matched'); // matched, discrepancy_rate, discrepancy_weight, discrepancy_both, unrecognized, duplicate
            $table->text('audit_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freight_invoice_items');
    }
};
