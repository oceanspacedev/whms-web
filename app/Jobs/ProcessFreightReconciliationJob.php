<?php

namespace App\Jobs;

use App\Models\FreightInvoice;
use App\Services\FreightReconciliationService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessFreightReconciliationJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public FreightInvoice $invoice
    ) {}

    /**
     * Execute the job.
     */
    public function handle(FreightReconciliationService $service): void
    {
        $this->invoice->update(['status' => 'processing']);

        try {
            $filePath = $this->invoice->file_path;
            if (! file_exists($filePath)) {
                $filePath = storage_path('app/'.$this->invoice->file_path);
            }

            if (! file_exists($filePath)) {
                throw new Exception("File invoice tidak ditemukan pada path: {$filePath}");
            }

            $rawItems = $service->parseInvoiceFile($filePath);
            $service->reconcile($this->invoice, $rawItems);

            Log::info("Audit invoice #{$this->invoice->invoice_number} selesai diproses.", [
                'total_items' => $this->invoice->total_items_count,
                'total_billed' => $this->invoice->total_billed_amount,
                'discrepancy' => $this->invoice->total_discrepancy_amount,
            ]);
        } catch (Exception $e) {
            Log::error("Gagal melakukan audit invoice #{$this->invoice->invoice_number}: {$e->getMessage()}");
            $this->invoice->update([
                'status' => 'disputed',
                'notes' => 'Error: '.$e->getMessage(),
            ]);
        }
    }
}
