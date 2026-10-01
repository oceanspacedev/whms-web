<?php

namespace App\Filament\Resources\TrackingOrders\Pages;

use App\Filament\Resources\TrackingOrders\TrackingOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTrackingOrder extends EditRecord
{
    protected static string $resource = TrackingOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
