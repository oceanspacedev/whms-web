<?php

namespace App\Models;

use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'no_po',
        'no_sj_supplier',
        'tanggal_po',
        'tanggal_datang',
        'nama_supplier',
        'nama_gudang',
        'alamat_gudang',
        'nama_kurir_ekspedisi',
        'no_resi',
        'penerima_gudang',
        'qty_koli',
        'qty_unit',
        'total_nominal',
        'keterangan_barang',
        'status_penerimaan',
        'catatan_gudang',
        'bukti_serah_terima',
        'status_verifikasi_finance',
        'catatan_finance',
        'verified_by',
        'verified_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_po' => 'date',
            'tanggal_datang' => 'date',
            'verified_at' => 'datetime',
            'total_nominal' => 'decimal:2',
            'qty_koli' => 'integer',
            'qty_unit' => 'integer',
        ];
    }
}
