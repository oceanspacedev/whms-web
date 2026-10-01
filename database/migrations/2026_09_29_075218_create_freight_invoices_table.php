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
        Schema::create('freight_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expedition_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->string('file_path')->nullable();
            $table->decimal('total_billed_amount', 15, 2)->default(0);
            $table->decimal('total_approved_amount', 15, 2)->default(0);
            $table->decimal('total_discrepancy_amount', 15, 2)->default(0);
            $table->unsignedInteger('total_items_count')->default(0);
            $table->unsignedInteger('matched_count')->default(0);
            $table->unsignedInteger('discrepancy_count')->default(0);
            $table->unsignedInteger('unrecognized_count')->default(0);
            $table->unsignedInteger('duplicate_count')->default(0);
            $table->string('status')->default('draft'); // draft, processing, audited, approved, disputed, paid
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freight_invoices');
    }
};
