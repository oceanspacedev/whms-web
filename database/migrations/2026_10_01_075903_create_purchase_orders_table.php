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
        if (! Schema::hasTable('purchase_orders')) {
            Schema::create('purchase_orders', function (Blueprint $table) {
                $table->id();
                $table->string('no_po')->index();
                $table->string('no_sj_supplier')->nullable();
                $table->date('tanggal_po')->nullable();
                $table->date('tanggal_datang')->nullable();
                $table->string('nama_supplier')->nullable();
                $table->string('nama_gudang')->nullable();
                $table->text('alamat_gudang')->nullable();
                $table->string('nama_kurir_ekspedisi')->nullable();
                $table->string('no_resi')->nullable();
                $table->string('penerima_gudang')->nullable();
                $table->integer('qty_koli')->default(1);
                $table->integer('qty_unit')->default(0);
                $table->decimal('total_nominal', 15, 2)->nullable();
                $table->text('keterangan_barang')->nullable();
                $table->string('status_penerimaan')->default('Lengkap');
                $table->text('catatan_gudang')->nullable();
                $table->string('bukti_serah_terima')->nullable();
                $table->string('status_verifikasi_finance')->default('Menunggu Pemeriksaan');
                $table->text('catatan_finance')->nullable();
                $table->string('verified_by')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
