<?php

namespace App\Filament\Resources\FreightInvoices\Pages;

use App\Filament\Resources\FreightInvoices\FreightInvoiceResource;
use App\Jobs\ProcessFreightReconciliationJob;
use App\Models\FreightInvoice;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CreateFreightInvoice extends CreateRecord
{
    protected static string $resource = FreightInvoiceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $relativePath = $data['invoice_file'];
        $fullPath = Storage::disk('local')->path($relativePath);

        /** @var FreightInvoice $record */
        $record = static::getModel()::create([
            'expedition_id' => $data['expedition_id'],
            'invoice_number' => $data['invoice_number'],
            'invoice_date' => $data['invoice_date'],
            'period_start' => $data['period_start'] ?? null,
            'period_end' => $data['period_end'] ?? null,
            'file_path' => $fullPath,
            'status' => 'processing',
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        // Dispatch background reconciliation job in Horizon
        ProcessFreightReconciliationJob::dispatch($record);

        Notification::make()
            ->title('Invoice Berhasil Diunggah')
            ->body('Mesin rekonsiliasi sedang mencocokkan resi dan mengaudit tarif kontrak di latar belakang.')
            ->success()
            ->send();

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
