<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubmitPodRequest extends FormRequest
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
            'nama_penerima' => ['required', 'string', 'max:255'],
            'foto_nota_sj' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,heic', 'max:12288'],
            'foto_penerima' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,heic', 'max:12288'],
            'address' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'nama_pengirim' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
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
            'nama_penerima.required' => 'Nama penerima di lokasi wajib diisi.',
            'foto_nota_sj.image' => 'File foto nota surat jalan harus berupa gambar (JPG/PNG/WEBP).',
            'foto_nota_sj.max' => 'Ukuran foto nota surat jalan maksimal 12 MB.',
            'foto_penerima.image' => 'File foto penerima harus berupa gambar (JPG/PNG/WEBP).',
            'foto_penerima.max' => 'Ukuran foto penerima maksimal 12 MB.',
        ];
    }
}
