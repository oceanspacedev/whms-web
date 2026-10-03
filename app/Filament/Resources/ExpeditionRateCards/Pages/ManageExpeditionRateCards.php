<?php

namespace App\Filament\Resources\ExpeditionRateCards\Pages;

use App\Filament\Resources\ExpeditionRateCards\ExpeditionRateCardResource;
use App\Services\RateCardImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Storage;

class ManageExpeditionRateCards extends ManageRecords
{
    protected static string $resource = ExpeditionRateCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Tarif Manual')
                ->icon(null),

            Action::make('importRateCards')
                ->label('Import Excel / Spreadsheet')
                ->icon(null)
                ->color('success')
                ->modalHeading('Import Tarif Kontrak (Rate Cards)')
                ->modalDescription('Unggah file spreadsheet Excel (.xlsx, .xls) atau CSV untuk memperbarui data master tarif ekspedisi.')
                ->modalSubmitActionLabel('Mulai Import Data')
                ->modalWidth('lg')
                ->form([
                    FileUpload::make('attachment')
                        ->label('File Spreadsheet (Excel / CSV)')
                        ->disk('local')
                        ->directory('rate-card-imports')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                            'text/plain',
                            'application/csv',
                        ])
                        ->maxSize(102400)
                        ->required()
                        ->helperText('Mendukung file unduhan Google Sheets / Excel (.xlsx dan .csv) hingga 100 MB.'),

                    Select::make('mode')
                        ->label('Metode Import')
                        ->options([
                            'upsert' => 'Tambahkan & Update data yang sudah ada (Upsert - Rekomendasi)',
                            'replace' => 'Kosongkan & Gantikan seluruh data (Reset & Import Ulang)',
                        ])
                        ->default('upsert')
                        ->required(),

                    TextInput::make('default_insurance_percent')
                        ->label('Default Persentase Asuransi (%)')
                        ->numeric()
                        ->default(0.2)
                        ->suffix('%')
                        ->required()
                        ->helperText('Nilai default premi asuransi jika tidak tertera di baris tarif (cth: 0.2%).'),
                ])
                ->action(function (array $data, RateCardImportService $importer): void {
                    $attachment = (string) ($data['attachment'] ?? '');
                    $filePath = Storage::disk('local')->path($attachment);

                    try {
                        $result = $importer->importFile(
                            filePath: $filePath,
                            mode: (string) ($data['mode'] ?? 'upsert'),
                            defaultInsurancePercent: (float) ($data['default_insurance_percent'] ?? 0.2)
                        );

                        Notification::make()
                            ->title('Import Tarif Berhasil')
                            ->body($result['message'])
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Gagal Import Data')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    } finally {
                        if (file_exists($filePath)) {
                            @unlink($filePath);
                        }
                    }
                }),
        ];
    }
}
