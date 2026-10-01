<?php

namespace Tests\Feature;

use App\Models\CsaImport;
use App\Models\CsaShipment;
use App\Models\User;
use App\Services\GoogleSheetWebhookService;
use Database\Seeders\WarehouseMappingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CsaImportPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WarehouseMappingSeeder::class);
    }

    public function test_warehouse_mappings_are_seeded(): void
    {
        $this->assertDatabaseHas('warehouse_mappings', [
            'csa_code' => 'GMCRB',
            'target_sheet' => 'CIREBON',
        ]);

        $this->assertDatabaseHas('warehouse_mappings', [
            'csa_code' => 'GMBDG',
            'target_sheet' => 'BANDUNG',
        ]);
    }

    public function test_csa_shipment_transformation_matches_google_sheet_columns(): void
    {
        $user = User::factory()->create();

        $import = CsaImport::create([
            'user_id' => $user->id,
            'file_name' => 'test.xlsx',
            'file_path' => '/tmp/test.xlsx',
            'status' => 'completed',
        ]);

        $shipment = CsaShipment::create([
            'csa_import_id' => $import->id,
            'no_sj' => 'SJ-202609001',
            'no_trans' => 'TRX-1001',
            'no_so' => 'SO-500',
            'tanggal_order' => '2026-09-01',
            'tanggal_kirim' => '2026-09-02',
            'badan_usaha' => 'PT. MEDIA SELULAR INDONESIA',
            'kode_gudang' => 'GMCRB',
            'nama_gudang' => 'GUDANG MSIS CIREBON',
            'target_sheet' => 'CIREBON',
            'tujuan_dealer' => 'ATLANTIC CELL',
            'alamat_kirim' => 'Jl. Tuparev',
            'nama_kota' => 'CIREBON',
            'brand' => 'REALME',
            'reff_note' => 'REALME - DP CIREBON',
            'total_nominal_sj' => 15000000.00,
            'qty_unit' => 10,
            'qty_koli' => 1,
            'berat' => 5.0,
            'ketentuan_biaya_kirim' => 'INVOICE',
            'nama_ekspedisi' => 'J&T',
            'no_resi_awb' => '1350082279',
            'biaya_kirim' => 50000.00,
            'status_pembayaran' => 'TAGIHAN BULANAN',
            'status_pengiriman' => 'ATLANTIC CELL',
            'ket_isi_unit' => '10x REALME',
        ]);

        $service = new GoogleSheetWebhookService;
        $row = $service->transformShipmentToRow($shipment);

        // Verify exactly 25 columns
        $this->assertCount(25, $row);

        // Verify key mapped fields
        $this->assertEquals('01/09/2026', $row[0]); // Tgl Order
        $this->assertEquals('02/09/2026', $row[1]); // Tgl Kirim
        $this->assertEquals('PT. MEDIA SELULAR INDONESIA', $row[2]); // Badan Usaha
        $this->assertEquals('CIREBON', $row[3]); // Target Sheet / Depo
        $this->assertEquals('ATLANTIC CELL', $row[4]); // Tujuan / Dealer
        $this->assertEquals('SJ-202609001', $row[8]); // Nomor SJ
        $this->assertEquals(15000000.0, $row[9]); // Nominal
        $this->assertEquals(10, $row[11]); // Qty Unit
        $this->assertEquals('J&T', $row[15]); // Ekspedisi
        $this->assertEquals('1350082279', $row[16]); // Resi
    }

    public function test_google_sheet_webhook_sync(): void
    {
        $user = User::factory()->create();

        $import = CsaImport::create([
            'user_id' => $user->id,
            'file_name' => 'test.xlsx',
            'file_path' => '/tmp/test.xlsx',
            'status' => 'completed',
        ]);

        $shipment = CsaShipment::create([
            'csa_import_id' => $import->id,
            'no_sj' => 'SJ-TEST-001',
            'target_sheet' => 'CIREBON',
            'kode_gudang' => 'GMCRB',
            'total_nominal_sj' => 5000000.00,
            'qty_unit' => 5,
        ]);

        Http::fake([
            'https://script.google.com/*' => Http::response([
                'status' => 'success',
                'sheet' => 'CIREBON',
                'inserted' => 1,
                'skipped' => 0,
            ], 200),
        ]);

        $service = new GoogleSheetWebhookService('https://script.google.com/macros/s/test/exec');
        $result = $service->syncShipments(collect([$shipment]));

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['inserted']);
        $this->assertDatabaseHas('csa_shipments', [
            'id' => $shipment->id,
            'is_synced' => true,
        ]);
    }
}
