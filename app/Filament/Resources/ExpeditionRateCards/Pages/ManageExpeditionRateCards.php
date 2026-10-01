<?php

namespace App\Filament\Resources\ExpeditionRateCards\Pages;

use App\Filament\Resources\ExpeditionRateCards\ExpeditionRateCardResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageExpeditionRateCards extends ManageRecords
{
    protected static string $resource = ExpeditionRateCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
