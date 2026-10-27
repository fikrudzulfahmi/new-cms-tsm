<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Selfie presensi: disk privat, dibaca ulang lewat GD (membuang EXIF), maks 800 px. */
class AttendanceProof
{
    public static function store(UploadedFile $file, int $sessionId): string
    {
        $fail = fn () => throw ValidationException::withMessages(['selfie' => 'Foto selfie tidak valid. Ambil ulang.']);
        if (! $file->isValid() || $file->getSize() > 3 * 1024 * 1024 || ! in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true)) {
            $fail();
        }
        $raw = @file_get_contents($file->getRealPath());
        $img = $raw !== false && function_exists('imagecreatefromstring') ? @imagecreatefromstring($raw) : false;
        if (! $img) {
            $fail();
        }
        $w = imagesx($img);
        $h = imagesy($img);
        $s = min(1, 800 / max($w, $h));
        $dst = imagecreatetruecolor(max(1, (int) round($w * $s)), max(1, (int) round($h * $s)));
        imagecopyresampled($dst, $img, 0, 0, 0, 0, imagesx($dst), imagesy($dst), $w, $h);
        ob_start();
        imagejpeg($dst, null, 80);
        $jpg = (string) ob_get_clean();

        $path = 'attendance/'.$sessionId.'/'.Str::random(24).'.jpg';
        Storage::disk('local')->put($path, $jpg);

        return $path;
    }

    public static function forget(?string $path): void
    {
        if ($path && str_starts_with($path, 'attendance/')) {
            Storage::disk('local')->delete($path);
        }
    }
}
