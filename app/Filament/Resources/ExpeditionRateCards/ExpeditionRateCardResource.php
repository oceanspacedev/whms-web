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
use Filament\Tables\Table;

class ExpeditionRateCardResource extends Resource
{
    protected static ?string $model = ExpeditionRateCard::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static \UnitEnum|string|null $navigationGroup = 'Audit & Tarif Ekspedisi';

    protected static ?string $navigationLabel = 'Tarif Kontrak (Rate Card)';

    protected static ?string $modelLabel = 'Tarif Kontrak PKS';

    protected static ?int $navigationSort = 3;

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
                TextInput::make('service_type')
                    ->required()
                    ->default('REG'),
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
                Toggle::make('is_active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('expedition.name')
                    ->searchable(),
                TextColumn::make('origin_depo')
                    ->searchable(),
                TextColumn::make('destination_city')
                    ->searchable(),
                TextColumn::make('destination_district')
                    ->searchable(),
                TextColumn::make('service_type')
                    ->searchable(),
                TextColumn::make('rate_per_kg')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('min_kg')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('insurance_rate_percent')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sla_days')
                    ->searchable(),
                IconColumn::make('is_active')
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
                //
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
