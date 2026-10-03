<?php

namespace App\Filament\Resources\ExpeditionRateCards;

use App\Filament\Resources\ExpeditionRateCards\Pages\ManageExpeditionRateCards;
use App\Models\ExpeditionRateCard;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ExpeditionRateCardResource extends Resource
{
    protected static ?string $model = ExpeditionRateCard::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static \UnitEnum|string|null $navigationGroup = 'Ekspedisi';

    protected static ?string $navigationLabel = 'Tarif Kontrak (Rate Card)';

    protected static ?string $modelLabel = 'Tarif Kontrak PKS';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('expedition_id')
                    ->relationship('expedition', 'name')
                    ->required(),
                TextInput::make('origin_depo')
                    ->required(),
                TextInput::make('destination_city')
                    ->required(),
                TextInput::make('destination_district'),
                TextInput::make('province')
                    ->label('Provinsi'),
                TextInput::make('service_type')
                    ->required()
                    ->default('DARAT'),
                TextInput::make('rate_per_kg')
                    ->required()
                    ->numeric(),
                TextInput::make('min_kg')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('insurance_rate_percent')
                    ->required()
                    ->numeric()
                    ->default(0.2),
                TextInput::make('sla_days'),
                TextInput::make('notes')
                    ->label('Catatan / Keterangan'),
                Toggle::make('is_active')
                    ->required()
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('expedition.name')
                    ->label('Ekspedisi')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('origin_depo')
                    ->label('Asal (Depo)')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('destination_city')
                    ->label('Kota Tujuan')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('destination_district')
                    ->label('Kecamatan')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('province')
                    ->label('Provinsi')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('service_type')
                    ->label('Layanan')
                    ->badge()
                    ->colors([
                        'primary' => 'DARAT',
                        'warning' => 'UDARA',
                        'info' => 'LAUT',
                    ])
                    ->sortable()
                    ->searchable(),
                TextColumn::make('rate_per_kg')
                    ->label('Tarif / KG')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('min_kg')
                    ->label('Min KG')
                    ->numeric()
                    ->suffix(' KG')
                    ->sortable(),
                TextColumn::make('insurance_rate_percent')
                    ->label('Premi (%)')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('sla_days')
                    ->label('SLA')
                    ->searchable(),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('expedition_id')
                    ->label('Ekspedisi')
                    ->relationship('expedition', 'name'),
                SelectFilter::make('service_type')
                    ->label('Layanan')
                    ->options([
                        'DARAT' => 'DARAT',
                        'UDARA' => 'UDARA',
                        'LAUT' => 'LAUT',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageExpeditionRateCards::route('/'),
        ];
    }
}
