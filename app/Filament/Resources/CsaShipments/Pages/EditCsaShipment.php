<?php

namespace App\Filament\Resources\CsaShipments\Pages;

use App\Filament\Resources\CsaShipments\CsaShipmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCsaShipment extends EditRecord
{
    protected static string $resource = CsaShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
