<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expedition extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'contact_person',
        'phone',
        'email',
        'is_active',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * @return HasMany<ExpeditionRateCard, $this>
     */
    public function rateCards(): HasMany
    {
        return $this->hasMany(ExpeditionRateCard::class);
    }

    /**
     * @return HasMany<FreightInvoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(FreightInvoice::class);
    }
}
