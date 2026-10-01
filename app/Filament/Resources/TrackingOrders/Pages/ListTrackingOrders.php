<?php

namespace App\Filament\Resources\TrackingOrders\Pages;

use App\Filament\Resources\TrackingOrders\TrackingOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrackingOrders extends ListRecords
{
    protected static string $resource = TrackingOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
