<?php

namespace App\Filament\Resources\CsaShipments\Pages;

use App\Filament\Resources\CsaShipments\CsaShipmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCsaShipments extends ListRecords
{
    protected static string $resource = CsaShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
