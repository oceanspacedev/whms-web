<?php

namespace App\Filament\Resources\TrackingOrders;

use App\Filament\Resources\TrackingOrders\Pages\CreateTrackingOrder;
use App\Filament\Resources\TrackingOrders\Pages\EditTrackingOrder;
use App\Filament\Resources\TrackingOrders\Pages\ListTrackingOrders;
use App\Filament\Resources\TrackingOrders\Pages\ViewTrackingOrder;
use App\Filament\Resources\TrackingOrders\Schemas\TrackingOrderForm;
use App\Filament\Resources\TrackingOrders\Tables\TrackingOrdersTable;
use App\Models\TrackingOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TrackingOrderResource extends Resource
{
    protected static ?string $model = TrackingOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static \UnitEnum|string|null $navigationGroup = 'Tracking Order';

    protected static ?string $navigationLabel = 'Surat Jalan';

    protected static ?string $modelLabel = 'Surat Jalan';

    protected static ?string $pluralModelLabel = 'Surat Jalan';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return TrackingOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrackingOrdersTable::configure($table);
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
            'index' => ListTrackingOrders::route('/'),
            'create' => CreateTrackingOrder::route('/create'),
            'view' => ViewTrackingOrder::route('/{record}'),
            'edit' => EditTrackingOrder::route('/{record}/edit'),
        ];
    }
}
