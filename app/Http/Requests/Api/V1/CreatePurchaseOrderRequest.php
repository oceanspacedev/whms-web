<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreatePurchaseOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'no_po' => ['required', 'string', 'max:100', 'unique:purchase_orders,no_po'],
            'no_sj_supplier' => ['nullable', 'string', 'max:100'],
            'tanggal_po' => ['nullable', 'date'],
            'tanggal_datang' => ['nullable', 'date'],
            'nama_supplier' => ['required', 'string', 'max:255'],
            'nama_gudang' => ['nullable', 'string', 'max:255'],
            'alamat_gudang' => ['nullable', 'string', 'max:1000'],
            'nama_kurir_ekspedisi' => ['nullable', 'string', 'max:255'],
            'no_resi' => ['nullable', 'string', 'max:100'],
            'penerima_gudang' => ['nullable', 'string', 'max:255'],
            'qty_koli' => ['nullable', 'integer', 'min:0'],
            'qty_unit' => ['nullable', 'integer', 'min:0'],
            'total_nominal' => ['nullable', 'numeric', 'min:0'],
            'keterangan_barang' => ['nullable', 'string', 'max:1000'],
            'status_penerimaan' => ['nullable', 'string', 'in:Lengkap,Kurang,Rusak,Belum Datang'],
            'catatan_gudang' => ['nullable', 'string', 'max:1000'],
            'bukti_serah_terima' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,heic', 'max:12288'],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'no_po.required' => 'Nomor Purchase Order (PO) wajib diisi.',
            'no_po.unique' => 'Nomor Purchase Order (PO) sudah terdaftar di sistem.',
            'nama_supplier.required' => 'Nama supplier wajib diisi.',
            'total_nominal.numeric' => 'Total nilai PO harus berupa nominal angka valid.',
            'bukti_serah_terima.image' => 'File bukti serah terima harus berupa file gambar (JPG/PNG/WEBP).',
        ];
    }
}
