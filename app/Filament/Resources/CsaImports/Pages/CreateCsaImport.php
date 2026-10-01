<?php

namespace App\Filament\Resources\CsaImports\Pages;

use App\Filament\Resources\CsaImports\CsaImportResource;
use App\Jobs\ProcessCsaImportJob;
use App\Models\CsaImport;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CreateCsaImport extends CreateRecord
{
    protected static string $resource = CsaImportResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $relativePath = $data['excel_file'];
        $fullPath = Storage::disk('local')->path($relativePath);
        $fileName = basename($relativePath);

        /** @var CsaImport $record */
        $record = static::getModel()::create([
            'user_id' => auth()->id(),
            'file_name' => $fileName,
            'file_path' => $fullPath,
            'status' => 'pending',
            'total_raw_rows' => 0,
            'total_shipments' => 0,
            'total_synced' => 0,
        ]);

        $autoSync = (bool) ($data['auto_sync'] ?? false);

        // Dispatch background job to process Excel in Horizon
        ProcessCsaImportJob::dispatch($record, $autoSync);

        Notification::make()
            ->title('File berhasil diunggah')
            ->body('Proses ekstraksi dan agregasi data sedang berjalan di antrean latar belakang (Horizon).')
            ->success()
            ->send();

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
