<?php

namespace App\Filament\Resources\FreightInvoices\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FreightInvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ringkasan Audit Faktur Ekspedisi')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('invoice_number')
                                    ->label('Nomor Faktur')
                                    ->weight('bold'),

                                TextEntry::make('expedition.name')
                                    ->label('Pihak Ekspedisi')
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('invoice_date')
                                    ->label('Tanggal Faktur')
                                    ->date('d F Y'),

                                TextEntry::make('status')
                                    ->label('Status Audit')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'approved' => 'success',
                                        'audited' => 'primary',
                                        'processing' => 'info',
                                        'disputed' => 'danger',
                                        'paid' => 'gray',
                                        default => 'warning',
                                    }),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('total_billed_amount')
                                    ->label('Total Ditagih Ekspedisi')
                                    ->money('IDR', locale: 'id')
                                    ->size('lg'),

                                TextEntry::make('total_approved_amount')
                                    ->label('Total Disetujui (Sesuai Kontrak & Fisik)')
                                    ->money('IDR', locale: 'id')
                                    ->color('success')
                                    ->size('lg')
                                    ->weight('bold'),

                                TextEntry::make('total_discrepancy_amount')
                                    ->label('Potongan / Koreksi Tagihan')
                                    ->money('IDR', locale: 'id')
                                    ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'gray')
                                    ->size('lg')
                                    ->weight('bold'),
                            ]),

                        Grid::make(5)
                            ->schema([
                                TextEntry::make('total_items_count')
                                    ->label('Total Resi Ditagih')
                                    ->numeric(),

                                TextEntry::make('matched_count')
                                    ->label('Resi Cocok (Matched)')
                                    ->numeric()
                                    ->color('success'),

                                TextEntry::make('discrepancy_count')
                                    ->label('Selisih Berat/Tarif')
                                    ->numeric()
                                    ->color(fn ($state) => (int) $state > 0 ? 'warning' : 'gray'),

                                TextEntry::make('unrecognized_count')
                                    ->label('Resi Tidak Dikenal')
                                    ->numeric()
                                    ->color(fn ($state) => (int) $state > 0 ? 'danger' : 'gray'),

                                TextEntry::make('duplicate_count')
                                    ->label('Tagihan Ganda (Duplikat)')
                                    ->numeric()
                                    ->color(fn ($state) => (int) $state > 0 ? 'danger' : 'gray'),
                            ]),
                    ]),

                Section::make('Rincian Temuan Selisih & Koreksi (Discrepancy Items)')
                    ->description('Daftar resi yang mengalami overcharge tarif kontrak, markup berat, atau nomor resi tidak terdaftar di pengiriman gudang.')
                    ->schema([
                        TextEntry::make('items')
                            ->label('')
                            ->formatStateUsing(function ($record): string {
                                $disputedItems = $record->items()
                                    ->where('audit_status', '!=', 'matched')
                                    ->take(100)
                                    ->get();

                                if ($disputedItems->isEmpty()) {
                                    return "<div class='text-sm text-success-600 font-semibold p-4 bg-success-50 dark:bg-success-950 rounded-lg'>✅ Seluruh nomor resi cocok dan sesuai dengan kontrak PKS serta data riil gudang (Tidak ada selisih tagihan).</div>";
                                }

                                $html = "<div class='overflow-x-auto'><table class='w-full text-left text-xs border-collapse'>";
                                $html .= "<thead><tr class='border-b font-semibold bg-gray-50 dark:bg-gray-800'>";
                                $html .= "<th class='py-2 px-2'>No Resi / SJ</th>";
                                $html .= "<th class='py-2 px-2'>Rute (Depo → Tujuan)</th>";
                                $html .= "<th class='py-2 px-2 text-right'>Berat Tagih vs Real</th>";
                                $html .= "<th class='py-2 px-2 text-right'>Tarif Tagih vs PKS</th>";
                                $html .= "<th class='py-2 px-2 text-right'>Total Ditagih</th>";
                                $html .= "<th class='py-2 px-2 text-right'>Seharusnya</th>";
                                $html .= "<th class='py-2 px-2 text-right text-danger-600'>Potongan</th>";
                                $html .= "<th class='py-2 px-2'>Status & Catatan Audit</th>";
                                $html .= '</tr></thead><tbody>';

                                foreach ($disputedItems as $item) {
                                    $billed = number_format($item->billed_total, 0, ',', '.');
                                    $expected = number_format($item->expected_total, 0, ',', '.');
                                    $selisih = number_format($item->discrepancy_amount, 0, ',', '.');
                                    $wTagih = $item->billed_weight;
                                    $wReal = $item->actual_weight !== null ? "{$item->actual_weight} kg" : '-';

                                    $badgeColor = match ($item->audit_status) {
                                        'unrecognized' => 'background:#fee2e2;color:#991b1b;',
                                        'duplicate' => 'background:#ffedd5;color:#9a3412;',
                                        'discrepancy_both' => 'background:#fef3c7;color:#92400e;',
                                        default => 'background:#f3f4f6;color:#374151;',
                                    };

                                    $statusLabel = match ($item->audit_status) {
                                        'unrecognized' => 'TIDAK DIKENAL',
                                        'duplicate' => 'DUPLIKAT',
                                        'discrepancy_weight' => 'MARKUP BERAT',
                                        'discrepancy_rate' => 'OVERCHARGE TARIF',
                                        'discrepancy_both' => 'BERAT & TARIF',
                                        'missing_rate' => 'TARIF PKS BELUM ADA',
                                        'missing_weight' => 'BERAT GUDANG KOSONG',
                                        default => $item->audit_status,
                                    };

                                    $html .= "<tr class='border-b hover:bg-gray-50/50 dark:hover:bg-gray-800/50'>";
                                    $html .= "<td class='py-2 px-2 font-mono font-bold'>{$item->no_resi_awb}<br><span class='text-gray-400 font-normal'>{$item->no_sj}</span></td>";
                                    $html .= "<td class='py-2 px-2'>{$item->origin_depo} → {$item->destination_city}</td>";
                                    $html .= "<td class='py-2 px-2 text-right'>{$wTagih} kg vs {$wReal}</td>";
                                    $html .= "<td class='py-2 px-2 text-right'>Rp ".number_format($item->billed_rate, 0, ',', '.').' vs Rp '.number_format($item->agreed_rate, 0, ',', '.').'</td>';
                                    $html .= "<td class='py-2 px-2 text-right font-mono'>Rp {$billed}</td>";
                                    $html .= "<td class='py-2 px-2 text-right font-mono text-success-600'>Rp {$expected}</td>";
                                    $html .= "<td class='py-2 px-2 text-right font-mono font-bold text-danger-600'>- Rp {$selisih}</td>";
                                    $html .= "<td class='py-2 px-2'><span style='padding:2px 6px;border-radius:4px;font-weight:600;font-size:10px;{$badgeColor}'>{$statusLabel}</span><br><span class='text-gray-500 text-[10px]'>{$item->audit_notes}</span></td>";
                                    $html .= '</tr>';
                                }

                                $html .= '</tbody></table></div>';

                                return $html;
                            })
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
