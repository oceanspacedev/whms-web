<?php

namespace Database\Seeders;

use App\Models\Expedition;
use App\Models\ExpeditionRateCard;
use Illuminate\Database\Seeder;

class ExpeditionRateCardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jnt = Expedition::where('code', 'JNT')->first();
        $rax = Expedition::where('code', 'RAX')->first();

        if (! $jnt || ! $rax) {
            return;
        }

        // Agreed corporate rate matrix from CIREBON and BANDUNG
        $rates = [
            // J&T from CIREBON
            ['expedition_id' => $jnt->id, 'origin_depo' => 'CIREBON', 'destination_city' => 'INDRAMAYU', 'rate_per_kg' => 8000, 'min_kg' => 1, 'insurance_rate_percent' => 0.2, 'sla_days' => '1'],
            ['expedition_id' => $jnt->id, 'origin_depo' => 'CIREBON', 'destination_city' => 'MAJALENGKA', 'rate_per_kg' => 8000, 'min_kg' => 1, 'insurance_rate_percent' => 0.2, 'sla_days' => '1'],
            ['expedition_id' => $jnt->id, 'origin_depo' => 'CIREBON', 'destination_city' => 'KUNINGAN', 'rate_per_kg' => 8000, 'min_kg' => 1, 'insurance_rate_percent' => 0.2, 'sla_days' => '1'],
            ['expedition_id' => $jnt->id, 'origin_depo' => 'CIREBON', 'destination_city' => 'TASIKMALAYA', 'rate_per_kg' => 10000, 'min_kg' => 1, 'insurance_rate_percent' => 0.2, 'sla_days' => '1-2'],
            ['expedition_id' => $jnt->id, 'origin_depo' => 'CIREBON', 'destination_city' => 'CIAMIS', 'rate_per_kg' => 10000, 'min_kg' => 1, 'insurance_rate_percent' => 0.2, 'sla_days' => '1-2'],
            ['expedition_id' => $jnt->id, 'origin_depo' => 'CIREBON', 'destination_city' => 'GARUT', 'rate_per_kg' => 10000, 'min_kg' => 1, 'insurance_rate_percent' => 0.2, 'sla_days' => '1-2'],
            ['expedition_id' => $jnt->id, 'origin_depo' => 'CIREBON', 'destination_city' => 'PANGANDARAN', 'rate_per_kg' => 11000, 'min_kg' => 1, 'insurance_rate_percent' => 0.2, 'sla_days' => '1-2'],
            ['expedition_id' => $jnt->id, 'origin_depo' => 'CIREBON', 'destination_city' => 'BANDUNG', 'rate_per_kg' => 9000, 'min_kg' => 1, 'insurance_rate_percent' => 0.2, 'sla_days' => '1'],
            ['expedition_id' => $jnt->id, 'origin_depo' => 'CIREBON', 'destination_city' => 'SUMEDANG', 'rate_per_kg' => 9000, 'min_kg' => 1, 'insurance_rate_percent' => 0.2, 'sla_days' => '1'],
            ['expedition_id' => $jnt->id, 'origin_depo' => 'CIREBON', 'destination_city' => 'SUBANG', 'rate_per_kg' => 9000, 'min_kg' => 1, 'insurance_rate_percent' => 0.2, 'sla_days' => '1'],

            // RAX from CIREBON
            ['expedition_id' => $rax->id, 'origin_depo' => 'CIREBON', 'destination_city' => 'TASIKMALAYA', 'rate_per_kg' => 7500, 'min_kg' => 2, 'insurance_rate_percent' => 0.15, 'sla_days' => '1-2'],
            ['expedition_id' => $rax->id, 'origin_depo' => 'CIREBON', 'destination_city' => 'SUMEDANG', 'rate_per_kg' => 7000, 'min_kg' => 2, 'insurance_rate_percent' => 0.15, 'sla_days' => '1'],
            ['expedition_id' => $rax->id, 'origin_depo' => 'CIREBON', 'destination_city' => 'GARUT', 'rate_per_kg' => 7500, 'min_kg' => 2, 'insurance_rate_percent' => 0.15, 'sla_days' => '1-2'],
            ['expedition_id' => $rax->id, 'origin_depo' => 'CIREBON', 'destination_city' => 'CIAMIS', 'rate_per_kg' => 7500, 'min_kg' => 2, 'insurance_rate_percent' => 0.15, 'sla_days' => '1-2'],
            ['expedition_id' => $rax->id, 'origin_depo' => 'CIREBON', 'destination_city' => 'BANDUNG', 'rate_per_kg' => 6500, 'min_kg' => 2, 'insurance_rate_percent' => 0.15, 'sla_days' => '1'],

            // J&T from BANDUNG
            ['expedition_id' => $jnt->id, 'origin_depo' => 'BANDUNG', 'destination_city' => 'CIREBON', 'rate_per_kg' => 9000, 'min_kg' => 1, 'insurance_rate_percent' => 0.2, 'sla_days' => '1'],
            ['expedition_id' => $jnt->id, 'origin_depo' => 'BANDUNG', 'destination_city' => 'TASIKMALAYA', 'rate_per_kg' => 8500, 'min_kg' => 1, 'insurance_rate_percent' => 0.2, 'sla_days' => '1'],
            ['expedition_id' => $jnt->id, 'origin_depo' => 'BANDUNG', 'destination_city' => 'GARUT', 'rate_per_kg' => 8000, 'min_kg' => 1, 'insurance_rate_percent' => 0.2, 'sla_days' => '1'],
        ];

        foreach ($rates as $rate) {
            ExpeditionRateCard::updateOrCreate(
                [
                    'expedition_id' => $rate['expedition_id'],
                    'origin_depo' => $rate['origin_depo'],
                    'destination_city' => $rate['destination_city'],
                ],
                $rate
            );
        }
    }
}
