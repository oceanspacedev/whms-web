<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            'qty_koli' => (int) $this->qty_koli,
            'qty_unit' => (int) $this->qty_unit,
            'total_nominal' => (float) $this->total_nominal,
            'keterangan_barang' => $this->keterangan_barang,
            'status_penerimaan' => $this->status_penerimaan,
            'status_verifikasi_finance' => $this->status_verifikasi_finance,
            'catatan_gudang' => $this->catatan_gudang,
            'catatan_finance' => $this->catatan_finance,
            'bukti_serah_terima' => $this->bukti_serah_terima,
            'bukti_serah_terima_url' => $this->resolvePhotoUrl($this->bukti_serah_terima, 'purchase-orders/bukti'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Resolve full accessible URL for uploaded proof photos.
     */
    protected function resolvePhotoUrl(?string $path, string $directory): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (Str::startsWith($path, 'purchase-orders/')) {
            return Storage::disk('public')->url($path);
        }

        return Storage::disk('public')->url("{$directory}/{$path}");
    }
}
