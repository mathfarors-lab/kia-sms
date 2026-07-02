<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DocumentService
{
    /**
     * Returns a base64 data URI for a photo stored on the public disk.
     * Returns null if the path is empty or the file does not exist.
     */
    public function photoDataUri(?string $photoPath): ?string
    {
        if (!$photoPath) {
            return null;
        }
        try {
            if (!Storage::disk('local')->exists($photoPath)) {
                return null;
            }
            $bytes = Storage::disk('local')->get($photoPath);
            $mime  = Storage::disk('local')->mimeType($photoPath) ?: 'image/jpeg';
            return "data:{$mime};base64," . base64_encode($bytes);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Generates a QR code SVG as a base64 data URI.
     * The payload is ONLY the stable code — no extra data.
     */
    public function qrDataUri(string $code): string
    {
        $svg = QrCode::format('svg')
            ->size(120)
            ->margin(1)
            ->generate($code);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Issues a sequential certificate number and persists the counter.
     * Format: TYPE-YEAR-NNNNN  (e.g. ENROLL-2026-00001)
     */
    public function nextCertNumber(string $type): string
    {
        $year = now()->year;
        $key  = "cert_seq_{$type}_{$year}";
        $seq  = (int) Setting::get($key, 0) + 1;
        Setting::set($key, (string) $seq, 'certificates');

        return strtoupper($type) . '-' . $year . '-' . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }
}
