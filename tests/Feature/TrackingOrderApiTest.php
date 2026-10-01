<?php

namespace Tests\Feature;

use App\Models\TrackingOrder;
use Database\Seeders\TrackingOrderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrackingOrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_tracking_orders_and_filter_by_courier(): void
    {
        $this->seed(TrackingOrderSeeder::class);

        $response = $this->getJson('/api/v1/tracking-orders?kurir=Heidy');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'no_sj',
                        'nama_dealer',
                        'alamat_dealer',
                        'jumlah_value_nota',
                        'jumlah_value_nota_formatted',
                        'tanggal_nota',
                        'tanggal_pengiriman',
                        'nama_pengirim',
                        'nama_penerima',
                        'foto_nota_sj',
                        'foto_nota_sj_url',
                        'foto_penerima',
                        'foto_penerima_url',
                        'address',
                        'status',
                        'is_delivered',
                    ],
                ],
            ]);

        $this->assertTrue(collect($response->json('data'))->every(fn ($item) => str_contains($item['nama_pengirim'], 'Heidy')));
    }

    public function test_can_find_tracking_order_by_surat_jalan_barcode(): void
    {
        $this->seed(TrackingOrderSeeder::class);

        $order = TrackingOrder::firstOrFail();

        $response = $this->getJson("/api/v1/tracking-orders/by-sj/{$order->no_sj}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.no_sj', $order->no_sj);

        $notFound = $this->getJson('/api/v1/tracking-orders/by-sj/NON-EXISTENT-SJ-999');
        $notFound->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_courier_can_upload_pod_photos_and_mark_as_delivered(): void
    {
        Storage::fake('public');

        $order = TrackingOrder::create([
            'no_sj' => 'SJ-MOBILE-TEST-001',
            'nama_dealer' => 'Dealer Bintang Selular',
            'alamat_dealer' => 'Jl. Merdeka No. 10 Bandung',
            'jumlah_value_nota' => 2500000,
            'tanggal_pengiriman' => now()->toDateString(),
            'nama_pengirim' => 'Budi Santoso',
            'status' => 'IN_TRANSIT',
        ]);

        $notaSjImage = UploadedFile::fake()->image('nota_sj_camera.jpg', 800, 600);
        $penerimaImage = UploadedFile::fake()->image('penerima_camera.jpg', 800, 600);

        $payload = [
            'nama_penerima' => 'Ibu Siti Khodijah (Kepala Toko)',
            'address' => 'Jl. Merdeka No. 10 Bandung',
            'latitude' => -6.917464,
            'longitude' => 107.619123,
            'notes' => 'Barang diterima lengkap dan segel utuh',
            'foto_nota_sj' => $notaSjImage,
            'foto_penerima' => $penerimaImage,
        ];

        $response = $this->postJson("/api/v1/tracking-orders/{$order->id}/submit-pod", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nama_penerima', 'Ibu Siti Khodijah (Kepala Toko)')
            ->assertJsonPath('data.status', 'DELIVERED')
            ->assertJsonPath('data.is_delivered', true);

        $order->refresh();
        $this->assertSame('DELIVERED', $order->status);
        $this->assertSame('Ibu Siti Khodijah (Kepala Toko)', $order->nama_penerima);
        $this->assertStringContainsString('GPS: [-6.917464, 107.619123]', $order->address);
        $this->assertNotNull($order->foto_nota_sj);
        $this->assertNotNull($order->foto_penerima);

        Storage::disk('public')->assertExists($order->foto_nota_sj);
        Storage::disk('public')->assertExists($order->foto_penerima);
    }

    public function test_courier_can_submit_pod_directly_by_no_sj(): void
    {
        Storage::fake('public');

        $order = TrackingOrder::create([
            'no_sj' => 'SJ-DIRECT-SCAN-889',
            'nama_dealer' => 'Toko Cahaya Abadi',
            'status' => 'PENDING',
        ]);

        $response = $this->postJson('/api/v1/tracking-orders/by-sj/SJ-DIRECT-SCAN-889/submit-pod', [
            'nama_penerima' => 'Pak Hendra',
            'notes' => 'Diserahkan langsung ke pemilik toko',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'DELIVERED');

        $order->refresh();
        $this->assertSame('DELIVERED', $order->status);
        $this->assertSame('Pak Hendra', $order->nama_penerima);
    }

    public function test_can_get_courier_drivers_list_and_summary(): void
    {
        $this->seed(TrackingOrderSeeder::class);

        $driversResponse = $this->getJson('/api/v1/courier/drivers');
        $driversResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data']);

        $summaryResponse = $this->getJson('/api/v1/courier/summary?kurir=Heidy');
        $summaryResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'total_assigned',
                    'total_delivered',
                    'total_in_transit',
                    'total_pending',
                    'total_returned',
                    'delivery_rate_percentage',
                ],
            ]);
    }

    public function test_auto_resolves_location_and_stamps_photo_when_courier_only_sends_coordinates(): void
    {
        Storage::fake('public');

        $order = TrackingOrder::create([
            'no_sj' => 'SJ-COORDS-ONLY-999',
            'nama_dealer' => 'Toko Elektronik Makmur',
            'alamat_dealer' => 'Jl. Purnawarman No. 12 Bandung',
            'status' => 'IN_TRANSIT',
        ]);

        $photo = UploadedFile::fake()->image('pod_captured.jpg', 1000, 800);

        // Courier takes photo and only GPS coordinates are sent from device, address is empty!
        $response = $this->postJson("/api/v1/tracking-orders/{$order->id}/submit-pod", [
            'nama_penerima' => 'Pak Joko',
            'latitude' => -6.903890,
            'longitude' => 107.618610,
            'foto_penerima' => $photo,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'DELIVERED');

        $order->refresh();
        // The address column was automatically populated with dealer address and GPS coordinates!
        $this->assertNotEmpty($order->address);
        $this->assertStringContainsString('GPS: [-6.90389, 107.61861]', $order->address);

        // Photo was saved with watermark to public storage
        $this->assertNotNull($order->foto_penerima);
        Storage::disk('public')->assertExists($order->foto_penerima);
    }
}
