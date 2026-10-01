<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PodLocationService
{
    /**
     * Reverse geocode coordinates to a human-readable street address.
     */
    public function reverseGeocode(float $latitude, float $longitude): ?string
    {
        try {
            $response = Http::timeout(3)
                ->withHeaders([
                    'User-Agent' => 'WMS-Logistics/1.0 (contact@wms.internal)',
                ])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'json',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'zoom' => 18,
                    'addressdetails' => 1,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (! empty($data['display_name'])) {
                    return $data['display_name'];
                }
            }
        } catch (\Throwable $e) {
            Log::info('Reverse geocoding unavailable, fallback to coordinates: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Extract GPS coordinates directly from photo EXIF metadata if available.
     *
     * @return array{latitude: float, longitude: float}|null
     */
    public function extractExifGps(string $filePath): ?array
    {
        if (! function_exists('exif_read_data') || ! file_exists($filePath)) {
            return null;
        }

        try {
            $exif = @exif_read_data($filePath, 'GPS');
            if (! $exif || ! isset($exif['GPSLatitude'], $exif['GPSLongitude'], $exif['GPSLatitudeRef'], $exif['GPSLongitudeRef'])) {
                return null;
            }

            $lat = $this->convertGpsCoordinate($exif['GPSLatitude'], $exif['GPSLatitudeRef']);
            $lon = $this->convertGpsCoordinate($exif['GPSLongitude'], $exif['GPSLongitudeRef']);

            if ($lat === null || $lon === null) {
                return null;
            }

            return [
                'latitude' => round($lat, 6),
                'longitude' => round($lon, 6),
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Stamp GPS location, timestamp, and Surat Jalan details onto the photo.
     */
    public function stampWatermarkOnPhoto(string $sourcePath, string $destinationPath, array $info): void
    {
        if (! file_exists($sourcePath)) {
            return;
        }

        $image = $this->createImageFromFile($sourcePath);
        if (! $image) {
            // Fallback: copy file as is
            copy($sourcePath, $destinationPath);

            return;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        // Adjust banner height proportionally
        $bannerHeight = max(60, min(140, (int) ($height * 0.12)));

        // Semi-transparent dark banner at the bottom
        imagealphablending($image, true);
        imagesavealpha($image, true);
        $darkOverlay = imagecolorallocatealpha($image, 15, 23, 42, 40); // #0f172a with opacity
        imagefilledrectangle($image, 0, $height - $bannerHeight, $width, $height, $darkOverlay);

        // Colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $amber = imagecolorallocate($image, 251, 191, 36);
        $slate = imagecolorallocate($image, 203, 213, 225);

        // Text lines
        $address = $info['address'] ?? 'Lokasi Serah Terima';
        // Limit address length to fit image width
        $maxChars = max(40, (int) ($width / 11));
        $addressLine = Str::limit($address, $maxChars, '...');

        $coords = '';
        if (isset($info['latitude'], $info['longitude'])) {
            $coords = "GPS: [{$info['latitude']}, {$info['longitude']}]";
        }

        $timeStr = now()->translatedFormat('d/m/Y H:i:s').' WIB';
        $sjStr = ! empty($info['no_sj']) ? "SJ: {$info['no_sj']}" : '';
        $courierStr = ! empty($info['courier']) ? "Kurir: {$info['courier']}" : '';

        $subLineParts = array_filter([$coords, $sjStr, $courierStr, $timeStr]);
        $subLine = implode('  |  ', $subLineParts);
        $subLine = Str::limit($subLine, $maxChars + 10, '...');

        // Vertical positions
        $y1 = (int) ($height - $bannerHeight + ($bannerHeight * 0.22));
        $y2 = (int) ($height - $bannerHeight + ($bannerHeight * 0.60));

        // Use built-in GD fonts (Font 4 for title, Font 2 for metadata)
        imagestring($image, 4, 18, $y1, 'LOKASI: '.$addressLine, $white);
        imagestring($image, 2, 18, $y2, $subLine, $amber);

        // Save watermarked JPEG
        imagejpeg($image, $destinationPath, 88);
        imagedestroy($image);
    }

    /**
     * Create GD image resource based on file type.
     *
     * @return \GdImage|resource|false
     */
    protected function createImageFromFile(string $path)
    {
        $mime = @mime_content_type($path) ?: '';

        return match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => @imagecreatefromstring(file_get_contents($path)),
        };
    }

    /**
     * Helper to convert EXIF GPS DMS array to decimal degree.
     */
    protected function convertGpsCoordinate(array $coord, string $hemi): ?float
    {
        $degrees = count($coord) > 0 ? $this->evalGpsRational($coord[0]) : 0;
        $minutes = count($coord) > 1 ? $this->evalGpsRational($coord[1]) : 0;
        $seconds = count($coord) > 2 ? $this->evalGpsRational($coord[2]) : 0;

        $flip = ($hemi === 'S' || $hemi === 'W') ? -1 : 1;

        return $flip * ($degrees + ($minutes / 60) + ($seconds / 3600));
    }

    /**
     * Helper to evaluate rational fractional strings like '7/1' or '4532/100'.
     */
    protected function evalGpsRational(string|int|float $val): float
    {
        if (is_numeric($val)) {
            return (float) $val;
        }

        $parts = explode('/', (string) $val);
        if (count($parts) === 2 && (float) $parts[1] !== 0.0) {
            return (float) $parts[0] / (float) $parts[1];
        }

        return (float) $val;
    }
}
