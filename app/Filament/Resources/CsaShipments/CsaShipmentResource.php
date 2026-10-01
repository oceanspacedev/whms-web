<?php

namespace App\Filament\Resources\CsaShipments;

use App\Filament\Resources\CsaShipments\Pages\CreateCsaShipment;
use App\Filament\Resources\CsaShipments\Pages\EditCsaShipment;
use App\Filament\Resources\CsaShipments\Pages\ListCsaShipments;
use App\Filament\Resources\CsaShipments\Schemas\CsaShipmentForm;
use App\Filament\Resources\CsaShipments\Tables\CsaShipmentsTable;
use App\Models\CsaShipment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CsaShipmentResource extends Resource
{
    protected static ?string $model = CsaShipment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static \UnitEnum|string|null $navigationGroup = 'Logistik & Ekspedisi';

    protected static ?string $navigationLabel = 'Surat Jalan Pengiriman';

    protected static ?string $modelLabel = 'Pengiriman SJ';

    protected static ?string $pluralModelLabel = 'Data Pengiriman SJ';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return CsaShipmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CsaShipmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCsaShipments::route('/'),
            'create' => CreateCsaShipment::route('/create'),
            'edit' => EditCsaShipment::route('/{record}/edit'),
        ];
    }
}
