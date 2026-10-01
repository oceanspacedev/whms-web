<?php

namespace App\Filament\Resources\FreightInvoices\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FreightInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Faktur Tagihan Ekspedisi')
                    ->description('Masukkan nomor invoice dan lampirkan rekap penagihan dari ekspedisi untuk diaudit secara otomatis.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('expedition_id')
                                    ->label('Pihak Ekspedisi')
                                    ->relationship('expedition', 'name')
                                    ->preload()
                                    ->searchable()
                                    ->required(),

                                TextInput::make('invoice_number')
                                    ->label('Nomor Faktur / Invoice')
                                    ->placeholder('Contoh: INV-JNT-2026-0901')
                                    ->required()
                                    ->unique(ignoreRecord: true),

                                DatePicker::make('invoice_date')
                                    ->label('Tanggal Faktur')
                                    ->default(now())
                                    ->required(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                DatePicker::make('period_start')
                                    ->label('Awal Periode Pengiriman'),

                                DatePicker::make('period_end')
                                    ->label('Akhir Periode Pengiriman'),
                            ]),

                        FileUpload::make('invoice_file')
                            ->label('File Tagihan Ekspedisi (Excel .xlsx / .csv)')
                            ->disk('local')
                            ->directory('freight_invoices')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'text/csv',
                                'text/plain',
                                'application/octet-stream',
                            ])
                            ->required()
                            ->maxSize(51200)
                            ->helperText('File berisi kolom Nomor Resi (AWB), Berat (kg), dan Total Biaya yang ditagihkan.'),

                        Textarea::make('notes')
                            ->label('Catatan Tambahan')
                            ->placeholder('Contoh: Tagihan J&T periode 1-15 September untuk all depo Jawa Barat')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
