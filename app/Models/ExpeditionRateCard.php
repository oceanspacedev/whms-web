<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpeditionRateCard extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'expedition_id',
        'origin_depo',
        'destination_city',
        'destination_district',
        'service_type',
        'rate_per_kg',
        'min_kg',
        'insurance_rate_percent',
        'sla_days',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'rate_per_kg' => 'decimal:2',
        'min_kg' => 'decimal:2',
        'insurance_rate_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<Expedition, $this>
     */
    public function expedition(): BelongsTo
    {
        return $this->belongsTo(Expedition::class);
    }
}
