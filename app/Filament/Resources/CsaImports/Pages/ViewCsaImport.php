<?php

namespace App\Filament\Resources\CsaImports\Pages;

use App\Filament\Resources\CsaImports\CsaImportResource;
use App\Jobs\ProcessCsaImportJob;
use App\Jobs\SyncToGoogleSheetJob;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCsaImport extends ViewRecord
{
    protected static string $resource = CsaImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncToSheets')
                ->label('Kirim ke Google Spreadsheet')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('success')
                ->visible(fn (): bool => $this->record->status === 'completed' && $this->record->total_shipments > 0)
                ->schema([
                    TextInput::make('webhook_url')
                        ->label('Google Apps Script Webhook URL')
                        ->default(config('services.google_sheets.webhook_url') ?: '')
                        ->required()
                        ->helperText('URL Web App Google Apps Script dari spreadsheet tujuan.'),

                    Select::make('target_sheet')
                        ->label('Pilih Sheet Tujuan')
                        ->placeholder('Semua Cabang / Sheet')
                        ->options(function () {
                            if (empty($this->record->summary_by_sheet)) {
                                return [];
                            }
                            $options = [];
                            foreach ($this->record->summary_by_sheet as $sheet => $stats) {
                                $options[$sheet] = "{$sheet} ({$stats['total_sj']} Surat Jalan)";
                            }

                            return $options;
                        }),
                ])
                ->action(function (array $data): void {
                    $targetSheet = $data['target_sheet'] ?? null;
                    $webhookUrl = $data['webhook_url'];

                    SyncToGoogleSheetJob::dispatch($this->record, $targetSheet, $webhookUrl);

                    Notification::make()
                        ->title('Proses Sinkronisasi Dimulai')
                        ->body('Data sedang dikirim ke Google Spreadsheet di antrean Horizon.')
                        ->success()
                        ->send();
                }),

            Action::make('reprocess')
                ->label('Ekstraksi Ulang File')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function (): void {
                    ProcessCsaImportJob::dispatch($this->record);

                    Notification::make()
                        ->title('Proses Ulang Dimulai')
                        ->body('File sedang diekstrak kembali oleh background worker.')
                        ->info()
                        ->send();
                }),
        ];
    }
}
