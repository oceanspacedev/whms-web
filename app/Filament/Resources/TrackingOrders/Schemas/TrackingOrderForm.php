<?php

namespace App\Filament\Resources\TrackingOrders\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TrackingOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Surat Jalan & Dealer')
                    ->description('Detail identitas dealer dan nomor surat jalan')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('nama_dealer')
                                    ->label('Nama Dealer')
                                    ->maxLength(255)
                                    ->placeholder('Contoh: PT BCA / Mega Lestari Jaya'),

                                TextInput::make('no_sj')
                                    ->label('Nomer Surat Jalan')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('Contoh: 2401304519'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('jumlah_value_nota')
                                    ->label('Jumlah Value Nota')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0),

                                DatePicker::make('tanggal_nota')
                                    ->label('Tanggal Nota')
                                    ->native(false),

                                DatePicker::make('tanggal_pengiriman')
                                    ->label('Tanggal Pengiriman')
                                    ->native(false),
                            ]),

                        Textarea::make('alamat_dealer')
                            ->label('Alamat Dealer')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Alamat lengkap dealer tujuan'),
                    ]),

                Section::make('Bukti Pengiriman & Penerimaan')
                    ->description('Dokumentasi serah terima barang, foto fisik, dan alamat')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('nama_pengirim')
                                    ->label('Nama Pengirim')
                                    ->maxLength(255)
                                    ->placeholder('Contoh: Heidy / Tomy / Fauzan'),

                                TextInput::make('nama_penerima')
                                    ->label('Nama Penerima')
                                    ->maxLength(255)
                                    ->placeholder('Contoh: Imas / Rini / Syifa'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                FileUpload::make('foto_nota_sj')
                                    ->label('Foto Nota Surat Jalan')
                                    ->image()
                                    ->directory('tracking-orders/nota')
                                    ->visibility('public')
                                    ->openable()
                                    ->downloadable(),

                                FileUpload::make('foto_penerima')
                                    ->label('Foto Penerima')
                                    ->image()
                                    ->directory('tracking-orders/penerima')
                                    ->visibility('public')
                                    ->openable()
                                    ->downloadable(),
                            ]),

                        Textarea::make('address')
                            ->label('Address / Alamat Serah Terima')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Contoh: Jalan Ibu Inggit Garnasih, Ciateul, Bandung City, West Java, Indonesia'),
                    ]),

                Section::make('Status & Catatan')
                    ->description('Status pengiriman dan keterangan operasional')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('status')
                                    ->label('Status Pengiriman')
                                    ->options([
                                        'DELIVERED' => 'Terkirim (Delivered)',
                                        'IN_TRANSIT' => 'Dalam Perjalanan (In Transit)',
                                        'PENDING' => 'Menunggu Kirim (Pending)',
                                        'RETURNED' => 'Retur / Dibatalkan (Returned)',
                                    ])
                                    ->default('DELIVERED')
                                    ->required()
                                    ->columnSpan(1),

                                Textarea::make('notes')
                                    ->label('Catatan Tambahan')
                                    ->rows(2)
                                    ->placeholder('Keterangan status atau kendala jika ada')
                                    ->columnSpan(2),
                            ]),
                    ]),
            ]);
    }
}
