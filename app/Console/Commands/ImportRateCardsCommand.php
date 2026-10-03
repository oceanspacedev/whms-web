<?php

namespace App\Console\Commands;

use App\Services\RateCardImportService;
use Illuminate\Console\Command;
use Throwable;

class ImportRateCardsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rate-cards:import
                            {file : Path ke file CSV atau Excel (.xlsx)}
                            {--mode=upsert : Mode import: upsert atau replace}
                            {--insurance=0.2 : Default persentase asuransi (%)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data tarif ekspedisi (Tarif Kontrak) dari spreadsheet/excel';

    /**
     * Execute the console command.
     */
    public function handle(RateCardImportService $service): int
    {
        $file = (string) $this->argument('file');
        $mode = (string) $this->option('mode');
        $insurance = (float) $this->option('insurance');

        if (! file_exists($file)) {
            $this->error("File tidak ditemukan: {$file}");

            return self::FAILURE;
        }

        $this->info("Memulai import file: {$file}");
        $this->info("Mode: {$mode} | Default Insurance: {$insurance}%");

        $start = microtime(true);

        try {
            $result = $service->importFile($file, $mode, $insurance);

            $duration = round(microtime(true) - $start, 2);

            $this->table(
                ['Metric', 'Nilai'],
                [
                    ['Total Baris File', number_format($result['total_rows'])],
                    ['Tarif Unik Terdeteksi', number_format($result['unique_records_count'])],
                    ['Tarif Ditambahkan (Baru)', number_format($result['imported_count'])],
                    ['Tarif Diupdate', number_format($result['updated_count'])],
                    ['Ekspedisi Baru Dibuat', number_format($result['new_expeditions_count'])],
                    ['Durasi Eksekusi', "{$duration} detik"],
                ]
            );

            $this->info($result['message']);

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Gagal import: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
