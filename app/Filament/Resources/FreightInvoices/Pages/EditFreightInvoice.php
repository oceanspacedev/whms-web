<?php

namespace App\Filament\Resources\FreightInvoices\Pages;

use App\Filament\Resources\FreightInvoices\FreightInvoiceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFreightInvoice extends EditRecord
{
    protected static string $resource = FreightInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
