<?php

namespace App\Filament\Resources\TrackingOrders\Pages;

use App\Filament\Resources\TrackingOrders\TrackingOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTrackingOrder extends CreateRecord
{
    protected static string $resource = TrackingOrderResource::class;
}
