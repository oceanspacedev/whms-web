<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TrackingOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'no_sj' => $this->no_sj,
            'nama_dealer' => $this->nama_dealer,
            'alamat_dealer' => $this->alamat_dealer,
            'jumlah_value_nota' => (float) $this->jumlah_value_nota,
            'jumlah_value_nota_formatted' => 'Rp '.number_format((float) $this->jumlah_value_nota, 0, ',', '.'),
            'tanggal_nota' => $this->tanggal_nota?->format('Y-m-d'),
            'tanggal_pengiriman' => $this->tanggal_pengiriman?->format('Y-m-d'),
            'nama_pengirim' => $this->nama_pengirim,
            'nama_penerima' => $this->nama_penerima,
            'foto_nota_sj' => $this->foto_nota_sj,
            'foto_nota_sj_url' => $this->resolvePhotoUrl($this->foto_nota_sj, 'tracking-orders/nota'),
            'foto_penerima' => $this->foto_penerima,
            'foto_penerima_url' => $this->resolvePhotoUrl($this->foto_penerima, 'tracking-orders/penerima'),
            'address' => $this->address,
            'status' => $this->status,
            'is_delivered' => $this->status === 'DELIVERED',
            'notes' => $this->notes,
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

        // If path already contains subfolder
        if (Str::startsWith($path, 'tracking-orders/')) {
            return Storage::disk('public')->url($path);
        }

        // Check if file exists directly in public disk
        if (Storage::disk('public')->exists("{$directory}/{$path}")) {
            return Storage::disk('public')->url("{$directory}/{$path}");
        }

        return Storage::disk('public')->url("{$directory}/{$path}");
    }
}
