<?php

namespace App\Filament\Resources\WarehouseMappings\Pages;

use App\Filament\Resources\WarehouseMappings\WarehouseMappingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWarehouseMappings extends ManageRecords
{
    protected static string $resource = WarehouseMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
