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
        Schema::create('csa_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('csa_import_id')->constrained()->cascadeOnDelete();
            $table->string('no_sj')->index();
            $table->string('no_trans')->nullable()->index();
            $table->string('no_so')->nullable()->index();
            $table->date('tanggal_order')->nullable();
            $table->date('tanggal_kirim')->nullable();
            $table->string('badan_usaha')->nullable();
            $table->string('kode_gudang')->index();
            $table->string('nama_gudang')->nullable();
            $table->string('target_sheet')->index();
            $table->string('tujuan_dealer')->nullable();
            $table->text('alamat_kirim')->nullable();
            $table->string('nama_kota')->nullable();
            $table->string('brand')->nullable();
            $table->string('reff_note')->nullable();
            $table->decimal('total_nominal_sj', 15, 2)->default(0);
            $table->unsignedInteger('qty_unit')->default(0);
            $table->unsignedInteger('qty_koli')->default(1);
            $table->decimal('berat', 8, 2)->default(1);
            $table->string('ketentuan_biaya_kirim')->nullable()->default('INVOICE');
            $table->string('nama_ekspedisi')->nullable();
            $table->string('no_resi_awb')->nullable();
            $table->decimal('biaya_kirim', 15, 2)->nullable();
            $table->string('status_pembayaran')->nullable()->default('TAGIHAN BULANAN');
            $table->string('status_pengiriman')->nullable();
            $table->date('tanggal_diterima')->nullable();
            $table->text('ket_isi_unit')->nullable();
            $table->boolean('is_synced')->default(false)->index();
            $table->timestamp('synced_at')->nullable();
            $table->text('sync_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('csa_shipments');
    }
};
