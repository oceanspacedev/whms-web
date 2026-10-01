<?php

namespace App\Services;

use App\Models\CsaShipment;
use App\Models\ExpeditionRateCard;
use App\Models\FreightInvoice;
use App\Models\FreightInvoiceItem;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FreightReconciliationService
{
    public function __construct(private XlsxReader $reader = new XlsxReader) {}

    /**
     * Run reconciliation and audit on a FreightInvoice from an array of parsed items or file.
     *
     * @param  array<int, array<string, mixed>>  $rawItems
     */
    public function reconcile(FreightInvoice $invoice, array $rawItems): FreightInvoice
    {
        return DB::transaction(function () use ($invoice, $rawItems) {
            // Delete previous items if re-auditing
            $invoice->items()->delete();

            $totalBilled = 0.0;
            $totalApproved = 0.0;
            $totalDiscrepancy = 0.0;
            $matchedCount = 0;
            $discrepancyCount = 0;
            $unrecognizedCount = 0;
            $duplicateCount = 0;

            // Pre-load active rate cards for this expedition
            $rateCards = ExpeditionRateCard::where('expedition_id', $invoice->expedition_id)
                ->where('is_active', true)
                ->get()
                ->keyBy(fn ($rc) => strtoupper(trim($rc->origin_depo)).'_'.strtoupper(trim($rc->destination_city)));

            // Pre-load previously approved resi numbers to catch duplicate invoicing
            $previousResi = FreightInvoiceItem::where('freight_invoice_id', '!=', $invoice->id)
                ->whereHas('invoice', fn ($q) => $q->whereIn('status', ['approved', 'paid']))
                ->pluck('freight_invoice_id', 'no_resi_awb')
                ->toArray();

            $itemsToInsert = [];
            $now = now();
            $shipmentsByResi = $this->shipmentsByIdentifier($rawItems, 'no_resi_awb');
            $shipmentsBySj = $this->shipmentsByIdentifier($rawItems, 'no_sj');

            foreach ($rawItems as $item) {
                $noResi = $this->reader->normalizeIdentifier((string) ($item['no_resi_awb'] ?? ''));
                $noSj = $this->reader->normalizeIdentifier((string) ($item['no_sj'] ?? ''));
                $billedWeight = (float) ($item['billed_weight'] ?? 1.0);
                $billedRate = (float) ($item['billed_rate'] ?? 0.0);
                $billedInsurance = (float) ($item['billed_insurance'] ?? 0.0);
                $billedTotal = (float) ($item['billed_total'] ?? 0.0);

                if ($billedTotal <= 0 && $billedRate > 0) {
                    $billedTotal = ($billedWeight * $billedRate) + $billedInsurance;
                }

                $totalBilled += $billedTotal;

                $auditStatus = 'matched';
                $auditNotes = [];
                $actualShipment = null;
                $actualWeight = null;
                $expectedTotal = $billedTotal;
                $agreedRate = 0.0;
                $agreedInsurance = 0.0;

                // 1. DUPLICATE CHECK
                if (! empty($noResi) && isset($previousResi[$noResi])) {
                    $auditStatus = 'duplicate';
                    $auditNotes[] = "Resi sudah pernah ditagihkan pada Faktur #{$previousResi[$noResi]}";
                    $expectedTotal = 0.0;
                    $duplicateCount++;
                } else {
                    if ($noResi !== '') {
                        $actualShipment = $shipmentsByResi->get($noResi);
                    } elseif ($noSj !== '') {
                        $actualShipment = $shipmentsBySj->get($noSj);
                    }

                    if (! $actualShipment) {
                        $auditStatus = 'unrecognized';
                        $auditNotes[] = 'Resi/SJ tidak ditemukan pada riil pengiriman seluruh depo Ocean Space';
                        $expectedTotal = 0.0;
                        $unrecognizedCount++;
                    } else {
                        $recordedWeight = (float) $actualShipment->berat;
                        $weightKnown = $recordedWeight > 0;
                        $originDepo = strtoupper(trim($item['origin_depo'] ?? $actualShipment->target_sheet ?? ''));
                        $destCity = strtoupper(trim($item['destination_city'] ?? $actualShipment->nama_kota ?? ''));

                        $hasWeightIssue = false;
                        if ($weightKnown) {
                            $actualWeight = $recordedWeight;
                            $weightDiscrepancy = round($billedWeight - $actualWeight, 2);
                            if ($weightDiscrepancy > 0.5) {
                                $hasWeightIssue = true;
                                $auditNotes[] = "Markup Berat: Ditagih {$billedWeight} kg vs Real Gudang {$actualWeight} kg (+{$weightDiscrepancy} kg)";
                            }
                        } else {
                            $auditNotes[] = 'Berat gudang belum tercatat, markup berat tidak dinilai';
                        }

                        // 4. RATE CARD AUDIT
                        $lookupKey = $originDepo.'_'.$destCity;
                        $rateCard = $rateCards->get($lookupKey);

                        $hasRateIssue = false;
                        $missingRate = false;
                        if ($rateCard) {
                            $agreedRate = (float) $rateCard->rate_per_kg;
                            $chargeableWeight = max((float) $rateCard->min_kg, $actualWeight);
                            $expectedBaseRate = $chargeableWeight * $agreedRate;

                            $insurancePercent = (float) $rateCard->insurance_rate_percent;
                            $totalNominal = (float) $actualShipment->total_nominal_sj;
                            $agreedInsurance = round($totalNominal * ($insurancePercent / 100), 2);

                            $expectedTotal = round($expectedBaseRate + $agreedInsurance, 2);

                            if ($billedRate > 0 && $billedRate > $agreedRate) {
                                $hasRateIssue = true;
                                $selisihPerKg = $billedRate - $agreedRate;
                                $auditNotes[] = "Tarif Melebihi Kontrak: Ditagih Rp {$billedRate}/kg vs PKS Rp {$agreedRate}/kg (+Rp {$selisihPerKg}/kg)";
                            }
                        } else {
                            $missingRate = true;
                            $auditNotes[] = "Tarif PKS untuk rute {$originDepo} -> {$destCity} belum terdaftar di sistem";
                            $expectedTotal = $billedTotal;
                        }

                        if ($hasWeightIssue && $hasRateIssue) {
                            $auditStatus = 'discrepancy_both';
                            $discrepancyCount++;
                        } elseif ($hasWeightIssue) {
                            $auditStatus = 'discrepancy_weight';
                            $discrepancyCount++;
                        } elseif ($hasRateIssue) {
                            $auditStatus = 'discrepancy_rate';
                            $discrepancyCount++;
                        } elseif ($missingRate) {
                            $auditStatus = 'missing_rate';
                            $discrepancyCount++;
                        } elseif (! $weightKnown) {
                            $auditStatus = 'missing_weight';
                            $discrepancyCount++;
                        } else {
                            $auditStatus = 'matched';
                            $matchedCount++;
                        }
                    }
                }

                $discrepancyAmount = round(max(0, $billedTotal - $expectedTotal), 2);
                $totalDiscrepancy += $discrepancyAmount;
                $totalApproved += $expectedTotal;

                $itemsToInsert[] = [
                    'freight_invoice_id' => $invoice->id,
                    'csa_shipment_id' => $actualShipment?->id,
                    'no_resi_awb' => $noResi ?: ($noSj ?: '-'),
                    'no_sj' => $noSj ?: ($actualShipment?->no_sj ?? null),
                    'origin_depo' => $item['origin_depo'] ?? ($actualShipment?->target_sheet ?? null),
                    'destination_city' => $item['destination_city'] ?? ($actualShipment?->nama_kota ?? null),
                    'billed_weight' => $billedWeight,
                    'actual_weight' => $actualWeight,
                    'weight_discrepancy' => $actualWeight !== null ? round($billedWeight - $actualWeight, 2) : 0.0,
                    'billed_rate' => $billedRate,
                    'agreed_rate' => $agreedRate,
                    'billed_insurance' => $billedInsurance,
                    'agreed_insurance' => $agreedInsurance,
                    'billed_total' => $billedTotal,
                    'expected_total' => $expectedTotal,
                    'discrepancy_amount' => $discrepancyAmount,
                    'audit_status' => $auditStatus,
                    'audit_notes' => implode(' | ', $auditNotes),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Batch insert items in chunks of 500
            foreach (array_chunk($itemsToInsert, 500) as $chunk) {
                DB::table('freight_invoice_items')->insert($chunk);
            }

            // Update parent invoice
            $invoice->update([
                'total_billed_amount' => $totalBilled,
                'total_approved_amount' => $totalApproved,
                'total_discrepancy_amount' => $totalDiscrepancy,
                'total_items_count' => count($itemsToInsert),
                'matched_count' => $matchedCount,
                'discrepancy_count' => $discrepancyCount,
                'unrecognized_count' => $unrecognizedCount,
                'duplicate_count' => $duplicateCount,
                'status' => 'audited',
            ]);

            return $invoice->fresh(['items']);
        });
    }

    /**
     * Parse Excel/CSV file from expedition invoice and convert into structured array.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parseInvoiceFile(string $filePath): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'csv' || $extension === 'txt') {
            return $this->parseCsv($filePath);
        }

        if ($extension === 'xlsx') {
            return $this->parseXlsx($filePath);
        }

        throw new Exception("Format file [{$extension}] tidak didukung. Harap unggah file .xlsx atau .csv");
    }

    /**
     * Parse CSV invoice file.
     */
    protected function parseCsv(string $filePath): array
    {
        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            $header = null;
            while (($data = fgetcsv($handle, 2000, ',')) !== false) {
                if (! $header) {
                    $header = array_map(fn ($h) => strtolower(trim($h)), $data);

                    continue;
                }
                $row = array_combine($header, array_pad($data, count($header), ''));
                $rows[] = $this->mapNormalizedFields($row);
            }
            fclose($handle);
        }

        return $rows;
    }

    /**
     * Parse XLSX invoice file using ZipArchive and XMLReader.
     */
    protected function parseXlsx(string $filePath): array
    {
        $sharedStrings = $this->reader->sharedStrings($filePath);
        $sheets = $this->reader->sheets($filePath);
        $invoiceSheet = null;

        foreach ($sheets as $sheetPath) {
            if ($this->sheetLooksLikeInvoice($filePath, $sheetPath, $sharedStrings)) {
                $invoiceSheet = $sheetPath;

                break;
            }
        }

        if ($invoiceSheet === null) {
            throw new Exception('Tidak menemukan sheet invoice ekspedisi pada file Excel');
        }

        $headerMap = [];
        $rows = [];

        foreach ($this->reader->iterateRows($filePath, $invoiceSheet, $sharedStrings) as $cells) {
            unset($cells['_row']);

            if ($headerMap === []) {
                $joined = strtolower(implode(' ', $cells));

                if ($this->textLooksLikeInvoiceHeader($joined)) {
                    foreach ($cells as $column => $name) {
                        $headerMap[$column] = strtolower(trim($name));
                    }
                }

                continue;
            }

            $mappedRow = [];

            foreach ($cells as $column => $value) {
                $field = $headerMap[$column] ?? $column;
                $mappedRow[$field] = $value;
            }

            $normalized = $this->mapNormalizedFields($mappedRow);

            if ($normalized['no_resi_awb'] !== '' || $normalized['no_sj'] !== '') {
                $rows[] = $normalized;
            }
        }

        return $rows;
    }

    /**
     * @param  array<int, string>  $sharedStrings
     */
    protected function sheetLooksLikeInvoice(string $filePath, string $sheetPath, array $sharedStrings): bool
    {
        $seen = 0;

        foreach ($this->reader->iterateRows($filePath, $sheetPath, $sharedStrings) as $cells) {
            $seen++;

            if ($this->textLooksLikeInvoiceHeader(strtolower(implode(' ', $cells)))) {
                return true;
            }

            if ($seen >= 15) {
                break;
            }
        }

        return false;
    }

    protected function textLooksLikeInvoiceHeader(string $joined): bool
    {
        $isDepotLog = str_contains($joined, 'nomor sj')
            && (str_contains($joined, 'depo') || str_contains($joined, 'berat'));

        if ($isDepotLog) {
            return false;
        }

        return str_contains($joined, 'shipment')
            || str_contains($joined, 'tariff')
            || str_contains($joined, 'tarif')
            || str_contains($joined, 'chargeable')
            || str_contains($joined, 'ongkir')
            || (str_contains($joined, 'resi') && str_contains($joined, 'biaya'));
    }

    /**
     * @param  array<int, array<string, mixed>>  $rawItems
     * @return Collection<string, CsaShipment>
     */
    protected function shipmentsByIdentifier(array $rawItems, string $field): Collection
    {
        $column = $field === 'no_resi_awb' ? 'no_resi_awb' : 'no_sj';
        $keys = [];

        foreach ($rawItems as $item) {
            $resi = $this->reader->normalizeIdentifier((string) ($item['no_resi_awb'] ?? ''));
            $value = $this->reader->normalizeIdentifier((string) ($item[$field] ?? ''));

            if ($field === 'no_sj' && $resi !== '') {
                continue;
            }

            if ($value !== '') {
                $keys[] = $value;
            }
        }

        $keys = array_values(array_unique($keys));

        if ($keys === []) {
            return collect();
        }

        return CsaShipment::query()
            ->whereIn($column, $keys)
            ->get()
            ->keyBy(fn (CsaShipment $shipment): string => $this->reader->normalizeIdentifier((string) $shipment->{$column}));
    }

    /**
     * Map flexible headers to standard reconciliation fields.
     */
    protected function mapNormalizedFields(array $row): array
    {
        $res = [
            'no_resi_awb' => '',
            'no_sj' => '',
            'origin_depo' => '',
            'destination_city' => '',
            'billed_weight' => 1.0,
            'billed_rate' => 0.0,
            'billed_insurance' => 0.0,
            'billed_total' => 0.0,
        ];

        foreach ($row as $key => $val) {
            $k = strtolower(trim((string) $key));
            $v = trim((string) $val);

            if (str_contains($k, 'resi') || str_contains($k, 'awb') || str_contains($k, 'shipment') || $k === 'cn') {
                $res['no_resi_awb'] = $this->reader->normalizeIdentifier($v);
            } elseif (str_contains($k, 'sj') || str_contains($k, 'surat jalan') || str_contains($k, 'faktur')) {
                $res['no_sj'] = $this->reader->normalizeIdentifier($v);
            } elseif (str_contains($k, 'asal') || str_contains($k, 'depo') || str_contains($k, 'origin')) {
                $res['origin_depo'] = $v;
            } elseif (str_contains($k, 'tujuan') || str_contains($k, 'kota') || str_contains($k, 'dest')) {
                $res['destination_city'] = $v;
            } elseif (str_contains($k, 'berat') || str_contains($k, 'weight') || str_contains($k, 'kg')) {
                $res['billed_weight'] = (float) $v;
            } elseif (str_contains($k, 'tarif') || str_contains($k, 'rate') || str_contains($k, 'harga/kg')) {
                $res['billed_rate'] = (float) $v;
            } elseif (str_contains($k, 'asuransi') || str_contains($k, 'insurance')) {
                $res['billed_insurance'] = (float) $v;
            } elseif (str_contains($k, 'total') || str_contains($k, 'ongkir') || str_contains($k, 'jumlah') || str_contains($k, 'biaya')) {
                $res['billed_total'] = (float) $v;
            }
        }

        return $res;
    }
}
