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
        Schema::create('tracking_orders', function (Blueprint $table) {
            $table->id();
            $table->string('no_sj')->index();
            $table->string('nama_dealer')->nullable()->index();
            $table->text('alamat_dealer')->nullable();
            $table->decimal('jumlah_value_nota', 15, 2)->default(0);
            $table->date('tanggal_nota')->nullable();
            $table->date('tanggal_pengiriman')->nullable();
            $table->string('nama_pengirim')->nullable()->index();
            $table->string('nama_penerima')->nullable()->index();
            $table->text('foto_nota_sj')->nullable();
            $table->text('foto_penerima')->nullable();
            $table->text('address')->nullable();
            $table->string('status')->default('DELIVERED')->index();
            $table->foreignId('csa_shipment_id')->nullable()->constrained('csa_shipments')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking_orders');
    }
};
