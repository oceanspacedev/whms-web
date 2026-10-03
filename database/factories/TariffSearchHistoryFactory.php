<?php

namespace Database\Factories;

use App\Models\TariffSearchHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TariffSearchHistory>
 */
class TariffSearchHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'origin' => 'CIREBON',
            'destination' => 'BANDUNG',
            'weight' => 5.0,
            'service' => 'DARAT',
            'total_results' => 1,
            'cheapest_expedition' => '21 EXPRES',
            'cheapest_cost' => 20000.0,
        ];
    }
}
