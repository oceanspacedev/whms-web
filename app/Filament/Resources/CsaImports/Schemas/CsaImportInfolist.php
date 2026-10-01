<?php

namespace App\Filament\Resources\CsaImports\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CsaImportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Ekstraksi Laporan CSA')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('file_name')
                                    ->label('Nama File')
                                    ->weight('bold'),

                                TextEntry::make('user.name')
                                    ->label('Pengunggah')
                                    ->placeholder('System'),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'completed' => 'success',
                                        'processing' => 'info',
                                        'failed' => 'danger',
                                        default => 'warning',
                                    }),

                                TextEntry::make('created_at')
                                    ->label('Waktu Upload')
                                    ->dateTime('d M Y H:i:s'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('total_raw_rows')
                                    ->label('Total Baris Mentah (IMEI/Item)')
                                    ->numeric()
                                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', '.')),

                                TextEntry::make('total_shipments')
                                    ->label('Total Surat Jalan (SJ) Teragregasi')
                                    ->numeric()
                                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', '.')),

                                TextEntry::make('total_synced')
                                    ->label('SJ Berhasil Dikirim ke Spreadsheet')
                                    ->numeric()
                                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', '.')),
                            ]),

                        TextEntry::make('error_message')
                            ->label('Pesan Kesalahan')
                            ->visible(fn ($record) => ! empty($record->error_message))
                            ->color('danger')
                            ->columnSpanFull(),
                    ]),

                Section::make('Rincian Surat Jalan per Cabang / Depo (Target Sheet)')
                    ->description('Daftar alokasi Surat Jalan yang siap dan telah dimasukkan ke masing-masing sheet cabang di Google Spreadsheet.')
                    ->schema([
                        TextEntry::make('summary_by_sheet')
                            ->label('')
                            ->formatStateUsing(function ($state): string {
                                if (empty($state) || ! is_array($state)) {
                                    return 'Belum ada data sheet.';
                                }

                                $output = "<div class='overflow-x-auto'><table class='w-full text-left text-sm border-collapse'>";
                                $output .= "<thead><tr class='border-b font-semibold bg-gray-50 dark:bg-gray-800'>";
                                $output .= "<th class='py-2 px-3'>Nama Tab Sheet</th>";
                                $output .= "<th class='py-2 px-3 text-right'>Total Surat Jalan</th>";
                                $output .= "<th class='py-2 px-3 text-right'>Total Unit Barang</th>";
                                $output .= "<th class='py-2 px-3 text-right'>Total Nominal (Rp)</th>";
                                $output .= '</tr></thead><tbody>';

                                ksort($state);
                                foreach ($state as $sheet => $stats) {
                                    $sj = number_format((int) ($stats['total_sj'] ?? 0), 0, ',', '.');
                                    $qty = number_format((int) ($stats['total_qty'] ?? 0), 0, ',', '.');
                                    $nominal = number_format((float) ($stats['total_nominal'] ?? 0), 0, ',', '.');

                                    $output .= "<tr class='border-b hover:bg-gray-50/50 dark:hover:bg-gray-800/50'>";
                                    $output .= "<td class='py-2 px-3 font-semibold text-primary-600 dark:text-primary-400'>{$sheet}</td>";
                                    $output .= "<td class='py-2 px-3 text-right font-mono'>{$sj}</td>";
                                    $output .= "<td class='py-2 px-3 text-right font-mono'>{$qty}</td>";
                                    $output .= "<td class='py-2 px-3 text-right font-mono'>Rp {$nominal}</td>";
                                    $output .= '</tr>';
                                }

                                $output .= '</tbody></table></div>';

                                return $output;
                            })
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
