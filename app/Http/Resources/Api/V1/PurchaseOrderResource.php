<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'no_po' => $this->no_po,
            'no_sj_supplier' => $this->no_sj_supplier,
            'tanggal_po' => $this->tanggal_po?->format('Y-m-d'),
            'tanggal_datang' => $this->tanggal_datang?->format('Y-m-d'),
            'nama_supplier' => $this->nama_supplier,
            'nama_gudang' => $this->nama_gudang,
            'alamat_gudang' => $this->alamat_gudang,
            'nama_kurir_ekspedisi' => $this->nama_kurir_ekspedisi,
            'no_resi' => $this->no_resi,
            'penerima_gudang' => $this->penerima_gudang,
            'qty_koli' => $this->qty_koli,
            'qty_unit' => $this->qty_unit,
            'keterangan_barang' => $this->keterangan_barang,
            'status_penerimaan' => $this->status_penerimaan,
        ];
    }
}
