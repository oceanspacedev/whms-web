<?php

namespace Database\Seeders;

use App\Models\Expedition;
use Illuminate\Database\Seeder;

class ExpeditionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $expeditions = [
            [
                'name' => 'J&T Express',
                'code' => 'JNT',
                'contact_person' => 'Account Manager Corporate J&T',
                'phone' => '021-80661888',
                'email' => 'corporate@jet.co.id',
                'is_active' => true,
                'notes' => 'PKS Rate Card Diskon Korporasi All Depo Ocean Space',
            ],
            [
                'name' => 'RAX Express / Cargo',
                'code' => 'RAX',
                'contact_person' => 'Ops Supervisor RAX',
                'phone' => '022-5201122',
                'email' => 'cs@rax.co.id',
                'is_active' => true,
                'notes' => 'Ekspedisi Rekanan Distribusi Jawa Barat & Sekitarnya',
            ],
            [
                'name' => 'JNE Express',
                'code' => 'JNE',
                'contact_person' => 'Sales Corporate JNE',
                'phone' => '021-29278888',
                'email' => 'customercare@jne.co.id',
                'is_active' => true,
                'notes' => 'Tarif Khusus Jalur Pengiriman Antar-Depo Luar Pulau',
            ],
            [
                'name' => 'SiCepat Ekspres',
                'code' => 'SICEPAT',
                'contact_person' => 'B2B Sales SiCepat',
                'phone' => '021-50200050',
                'email' => 'corporate@sicepat.com',
                'is_active' => true,
                'notes' => 'Pengiriman Regular & Best Service',
            ],
        ];

        foreach ($expeditions as $item) {
            Expedition::updateOrCreate(['code' => $item['code']], $item);
        }
    }
}
