<?php

namespace Tests\Feature;

use App\Jobs\ProcessCsaImportJob;
use App\Jobs\ProcessFreightReconciliationJob;
use App\Models\CsaImport;
use App\Models\CsaShipment;
use App\Models\Expedition;
use App\Models\FreightInvoice;
use App\Models\User;
use App\Services\CsaExcelParserService;
use App\Services\FreightReconciliationService;
use Database\Seeders\WarehouseMappingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InformasiDataEvaluationTest extends TestCase
{
    use RefreshDatabase;

    public function test_september_sales_report_groups_sj_and_sums_jumlah(): void
    {
        $path = $this->informasiFile('LAP PENJUALAN MSI SEPTEMBER 2026.xlsx');
        $this->seed(WarehouseMappingSeeder::class);

        $result = app(CsaExcelParserService::class)->parseAndAggregate($path);
        $shipment = collect($result['shipments'])->firstWhere('no_sj', '2609000003');

        $this->assertSame(73898, $result['total_raw_rows']);
        $this->assertSame(23188, $result['total_shipments']);
        $this->assertIsArray($shipment);
        $this->assertSame('WMONL', $shipment['kode_gudang']);
        $this->assertSame('JAKARTA PIK', $shipment['target_sheet']);
        $this->assertSame('SHOPEE - ONLINE SHOPEE', $shipment['tujuan_dealer']);
        $this->assertSame('ID268186114776M', $shipment['no_resi_awb']);
        $this->assertSame('TECNO', $shipment['brand']);
        $this->assertSame('2026-09-01', $shipment['tanggal_kirim']);
        $this->assertSame(2, $shipment['qty_unit']);
        $this->assertEqualsWithDelta(4017117.1171, $shipment['total_nominal_sj'], 0.02);
        $this->assertNull($shipment['nama_ekspedisi']);
        $this->assertSame(0.0, $shipment['berat']);
    }

    public function test_august_invoice_reads_shipment_number_weight_and_tariff(): void
    {
        $path = $this->informasiFile('MSI AGUSTUS 2026.xlsx');

        $rows = app(FreightReconciliationService::class)->parseInvoiceFile($path);
        $row = collect($rows)->firstWhere('no_resi_awb', '100240387353');

        $this->assertGreaterThan(400, count($rows));
        $this->assertIsArray($row);
        $this->assertSame('BOGOR', $row['destination_city']);
        $this->assertSame(15.0, $row['billed_weight']);
        $this->assertSame(1500.0, $row['billed_rate']);
        $this->assertSame(22500.0, $row['billed_total']);
    }

    public function test_august_invoice_links_awb_to_depot_shipment(): void
    {
        $path = $this->informasiFile('MSI AGUSTUS 2026.xlsx');
        $user = User::factory()->create();

        $import = CsaImport::create([
            'user_id' => $user->id,
            'file_name' => 'MSI AGUSTUS 2026.xlsx',
            'file_path' => $path,
            'status' => 'pending',
        ]);

        ProcessCsaImportJob::dispatchSync($import, false);
        $import->refresh();

        $this->assertSame('completed', $import->status, (string) $import->error_message);

        $shipment = CsaShipment::query()->where('no_resi_awb', '100240387353')->first();

        $this->assertInstanceOf(CsaShipment::class, $shipment);
        $this->assertSame('2608000490', $shipment->no_sj);
        $this->assertSame('21 EXPRESS', $shipment->nama_ekspedisi);
        $this->assertSame('JAKARTA PIK', $shipment->target_sheet);
        $this->assertSame('KAB.BOGOR', $shipment->nama_kota);
        $this->assertSame('2026-08-01', $shipment->tanggal_kirim->toDateString());
        $this->assertSame(10, $shipment->qty_unit);
        $this->assertEqualsWithDelta(14.64, (float) $shipment->berat, 0.01);

        $expedition = Expedition::create([
            'name' => '21 Express',
            'code' => '21EX',
        ]);

        $invoice = FreightInvoice::create([
            'expedition_id' => $expedition->id,
            'invoice_number' => 'MSI-AGUSTUS-2026',
            'invoice_date' => '2026-08-31',
            'file_path' => $path,
            'status' => 'draft',
        ]);

        ProcessFreightReconciliationJob::dispatchSync($invoice);
        $invoice->refresh();

        $this->assertSame('audited', $invoice->status, (string) $invoice->notes);

        $item = $invoice->items()->where('no_resi_awb', '100240387353')->first();

        $this->assertNotNull($item);
        $this->assertSame($shipment->id, $item->csa_shipment_id);
        $this->assertSame('missing_rate', $item->audit_status);
        $this->assertEqualsWithDelta(15.0, (float) $item->billed_weight, 0.001);
        $this->assertEqualsWithDelta(14.64, (float) $item->actual_weight, 0.01);
        $this->assertEqualsWithDelta(22500.0, (float) $item->billed_total, 0.01);
        $this->assertGreaterThan(400, $invoice->total_items_count);
        $this->assertLessThan(30, $invoice->unrecognized_count);
    }

    private function informasiFile(string $name): string
    {
        $path = base_path('informasidata/'.$name);

        if (! is_file($path)) {
            $this->markTestSkipped("File data tidak ada: {$name}");
        }

        return $path;
    }
}
