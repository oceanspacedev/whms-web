<?php

namespace App\Services;

use App\Models\CsaShipment;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleSheetWebhookService
{
    protected string $webhookUrl;

    public function __construct(?string $webhookUrl = null)
    {
        $this->webhookUrl = $webhookUrl ?? (string) config('services.google_sheets.webhook_url', env('GOOGLE_SHEET_WEBHOOK_URL', ''));
    }

    /**
     * Set or override webhook URL.
     */
    public function setWebhookUrl(string $url): self
    {
        $this->webhookUrl = $url;

        return $this;
    }

    /**
     * Sync a collection of CsaShipment models to Google Spreadsheet.
     *
     * @param  Collection<int, CsaShipment>  $shipments
     * @return array{
     *     success: bool,
     *     total: int,
     *     inserted: int,
     *     skipped: int,
     *     errors: array<int, string>
     * }
     */
    public function syncShipments(Collection $shipments): array
    {
        if (empty($this->webhookUrl)) {
            throw new Exception('Google Sheet Webhook URL belum dikonfigurasi. Silakan isi di .env (GOOGLE_SHEET_WEBHOOK_URL) atau form pengaturan.');
        }

        // Group shipments by target sheet
        $grouped = $shipments->groupBy('target_sheet');
        $totalInserted = 0;
        $totalSkipped = 0;
        $errors = [];

        foreach ($grouped as $sheetName => $items) {
            // Chunk rows into batches of 100 to avoid Google Apps Script timeout
            $chunks = $items->chunk(100);

            foreach ($chunks as $chunk) {
                $rows = [];
                $shipmentIds = [];

                foreach ($chunk as $shipment) {
                    $rows[] = $this->transformShipmentToRow($shipment);
                    $shipmentIds[] = $shipment->id;
                }

                try {
                    $response = Http::timeout(60)
                        ->retry(2, 1000)
                        ->post($this->webhookUrl, [
                            'sheetName' => (string) $sheetName,
                            'rows' => $rows,
                        ]);

                    if ($response->successful()) {
                        $json = $response->json();

                        $inserted = (int) ($json['inserted'] ?? count($rows));
                        $skipped = (int) ($json['skipped'] ?? $json['skipped_duplicate'] ?? 0);

                        $totalInserted += $inserted;
                        $totalSkipped += $skipped;

                        // Mark shipments as synced in DB
                        CsaShipment::whereIn('id', $shipmentIds)->update([
                            'is_synced' => true,
                            'synced_at' => now(),
                            'sync_error' => null,
                        ]);
                    } else {
                        $errMsg = "HTTP {$response->status()}: {$response->body()}";
                        $errors[] = "Sheet [{$sheetName}]: {$errMsg}";

                        CsaShipment::whereIn('id', $shipmentIds)->update([
                            'sync_error' => $errMsg,
                        ]);
                    }
                } catch (Exception $e) {
                    $errMsg = $e->getMessage();
                    $errors[] = "Sheet [{$sheetName}]: {$errMsg}";

                    Log::error("GoogleSheetWebhook error on sheet {$sheetName}", [
                        'error' => $errMsg,
                    ]);

                    CsaShipment::whereIn('id', $shipmentIds)->update([
                        'sync_error' => $errMsg,
                    ]);
                }
            }
        }

        return [
            'success' => empty($errors),
            'total' => $shipments->count(),
            'inserted' => $totalInserted,
            'skipped' => $totalSkipped,
            'errors' => $errors,
        ];
    }

    /**
     * Transform CsaShipment model into 25-column Google Sheet row array.
     *
     * @return array<int, mixed>
     */
    public function transformShipmentToRow(CsaShipment $shipment): array
    {
        $tglOrder = $shipment->tanggal_order ? $shipment->tanggal_order->format('d/m/Y') : '';
        $tglKirim = $shipment->tanggal_kirim ? $shipment->tanggal_kirim->format('d/m/Y') : '';

        return [
            $tglOrder,                                             // 1. TANGGAL ORDER
            $tglKirim,                                             // 2. TANGGAL KIRIM
            $shipment->badan_usaha ?? '',                          // 3. BADAN USAHA
            $shipment->target_sheet ?: $shipment->nama_gudang,     // 4. DEPO [WAREHOUSE]
            $shipment->tujuan_dealer,                              // 5. TUJUAN/DEALER
            $shipment->alamat_kirim ?? '',                         // 6. ALAMAT KIRIM
            $shipment->nama_kota ?? '',                            // 7. NAMA KOTA
            $shipment->brand ?? '',                                // 8. BRAND
            $shipment->no_sj,                                      // 9. NOMOR SJ
            (float) $shipment->total_nominal_sj,                   // 10. TOTAL NOMINAL SJ
            $shipment->reff_note ?? '',                            // 11. REFFNOTE
            (int) $shipment->qty_unit,                             // 12. QTY UNIT
            (int) $shipment->qty_koli,                             // 13. QTY KOLI
            (float) $shipment->berat,                              // 14. BERAT
            $shipment->ketentuan_biaya_kirim ?? '',                // 15. KETENTUAN BIAYA KIRIM
            $shipment->nama_ekspedisi ?? '',                       // 16. NAMA EKSPEDISI
            $shipment->no_resi_awb ?? '',                          // 17. NO RESI AWB
            $shipment->biaya_kirim ? (float) $shipment->biaya_kirim : '', // 18. BIAYA KIRIM
            $shipment->status_pembayaran ?? '',                    // 19. STATUS PEMBAYARAN
            $shipment->status_pengiriman ?? '',                    // 20. STATUS PENGIRIMAN
            $shipment->tanggal_diterima ? $shipment->tanggal_diterima->format('d/m/Y') : '', // 21. TANGGAL DITERIMA
            '',                                                    // 22. LEAD TIME PROSES
            '',                                                    // 23. LEAD TIME KIRIM
            '',                                                    // 24. LEAD TIME KESELURUHAN
            $shipment->ket_isi_unit ?: (string) $shipment->qty_unit, // 25. KET. ISI UNIT
        ];
    }
}
