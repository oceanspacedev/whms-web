<?php

namespace Tests\Feature;

use App\Models\CsaImport;
use App\Models\CsaShipment;
use App\Models\Expedition;
use App\Models\FreightInvoice;
use App\Models\User;
use App\Services\FreightReconciliationService;
use Database\Seeders\ExpeditionRateCardSeeder;
use Database\Seeders\ExpeditionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreightReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected Expedition $expedition;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExpeditionSeeder::class);
        $this->seed(ExpeditionRateCardSeeder::class);

        $this->expedition = Expedition::where('code', 'JNT')->firstOrFail();
        $this->user = User::factory()->create();
    }

    public function test_matched_shipment_audited_correctly(): void
    {
        $import = CsaImport::create([
            'user_id' => $this->user->id,
            'file_name' => 'sales.xlsx',
            'file_path' => '/tmp/sales.xlsx',
            'status' => 'completed',
        ]);

        $shipment = CsaShipment::create([
            'csa_import_id' => $import->id,
            'no_sj' => 'SJ-CRB-001',
            'no_resi_awb' => 'JNT12345678',
            'kode_gudang' => 'GMCRB',
            'target_sheet' => 'CIREBON',
            'nama_kota' => 'INDRAMAYU',
            'berat' => 2.0,
            'total_nominal_sj' => 1000000.0,
        ]);

        $invoice = FreightInvoice::create([
            'expedition_id' => $this->expedition->id,
            'invoice_number' => 'INV-TEST-001',
            'invoice_date' => now(),
            'status' => 'processing',
        ]);

        // Rate card CIREBON -> INDRAMAYU is Rp 8,000/kg with 0.2% insurance
        // Expected base = 2kg * 8,000 = 16,000
        // Expected insurance = 1,000,000 * 0.2% = 2,000
        // Expected total = 18,000

        $rawItems = [
            [
                'no_resi_awb' => 'JNT12345678',
                'no_sj' => 'SJ-CRB-001',
                'origin_depo' => 'CIREBON',
                'destination_city' => 'INDRAMAYU',
                'billed_weight' => 2.0,
                'billed_rate' => 8000.0,
                'billed_insurance' => 2000.0,
                'billed_total' => 18000.0,
            ],
        ];

        $service = new FreightReconciliationService;
        $result = $service->reconcile($invoice, $rawItems);

        $this->assertEquals('audited', $result->status);
        $this->assertEquals(1, $result->total_items_count);
        $this->assertEquals(1, $result->matched_count);
        $this->assertEquals(0, $result->discrepancy_count);
        $this->assertEquals(18000.0, $result->total_billed_amount);
        $this->assertEquals(18000.0, $result->total_approved_amount);
        $this->assertEquals(0.0, $result->total_discrepancy_amount);

        $item = $result->items->first();
        $this->assertEquals('matched', $item->audit_status);
        $this->assertEquals($shipment->id, $item->csa_shipment_id);
    }

    public function test_detects_unrecognized_ghost_shipment(): void
    {
        $invoice = FreightInvoice::create([
            'expedition_id' => $this->expedition->id,
            'invoice_number' => 'INV-GHOST-001',
            'invoice_date' => now(),
            'status' => 'processing',
        ]);

        $rawItems = [
            [
                'no_resi_awb' => 'FIKTIF-999999',
                'no_sj' => 'SJ-UNKNOWN',
                'origin_depo' => 'CIREBON',
                'destination_city' => 'JAKARTA',
                'billed_weight' => 5.0,
                'billed_rate' => 15000.0,
                'billed_total' => 75000.0,
            ],
        ];

        $service = new FreightReconciliationService;
        $result = $service->reconcile($invoice, $rawItems);

        $this->assertEquals(1, $result->unrecognized_count);
        $this->assertEquals(75000.0, $result->total_billed_amount);
        $this->assertEquals(0.0, $result->total_approved_amount);
        $this->assertEquals(75000.0, $result->total_discrepancy_amount);

        $item = $result->items->first();
        $this->assertEquals('unrecognized', $item->audit_status);
        $this->assertEquals(75000.0, $item->discrepancy_amount);
    }

    public function test_detects_rate_overcharge_and_weight_markup(): void
    {
        $import = CsaImport::create([
            'user_id' => $this->user->id,
            'file_name' => 'sales.xlsx',
            'file_path' => '/tmp/sales.xlsx',
            'status' => 'completed',
        ]);

        // Real weight in warehouse is 2 kg
        CsaShipment::create([
            'csa_import_id' => $import->id,
            'no_sj' => 'SJ-CRB-DISC',
            'no_resi_awb' => 'JNT88889999',
            'kode_gudang' => 'GMCRB',
            'target_sheet' => 'CIREBON',
            'nama_kota' => 'TASIKMALAYA',
            'berat' => 2.0,
            'total_nominal_sj' => 5000000.0,
        ]);

        $invoice = FreightInvoice::create([
            'expedition_id' => $this->expedition->id,
            'invoice_number' => 'INV-DISC-001',
            'invoice_date' => now(),
            'status' => 'processing',
        ]);

        // PKS rate for CIREBON -> TASIKMALAYA is Rp 10,000/kg
        // Actual weight: 2 kg -> Expected base: 20,000, insurance: 10,000 -> Expected total: 30,000
        // Expedition billed: 4 kg (+2 kg markup!) at Rp 15,000/kg (+5,000 overcharge!) -> Billed total: 60,000

        $rawItems = [
            [
                'no_resi_awb' => 'JNT88889999',
                'no_sj' => 'SJ-CRB-DISC',
                'origin_depo' => 'CIREBON',
                'destination_city' => 'TASIKMALAYA',
                'billed_weight' => 4.0,
                'billed_rate' => 15000.0,
                'billed_insurance' => 0.0,
                'billed_total' => 60000.0,
            ],
        ];

        $service = new FreightReconciliationService;
        $result = $service->reconcile($invoice, $rawItems);

        $this->assertEquals(1, $result->discrepancy_count);
        $this->assertEquals(60000.0, $result->total_billed_amount);
        $this->assertEquals(30000.0, $result->total_approved_amount);
        $this->assertEquals(30000.0, $result->total_discrepancy_amount);

        $item = $result->items->first();
        $this->assertEquals('discrepancy_both', $item->audit_status);
        $this->assertEquals(2.0, $item->weight_discrepancy);
        $this->assertEquals(30000.0, $item->discrepancy_amount);
    }
}
