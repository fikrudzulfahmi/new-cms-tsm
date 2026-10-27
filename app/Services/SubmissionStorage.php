<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Simpan lampiran tugas di disk privat.
 * - Whitelist ekstensi + MIME hasil deteksi isi berkas (bukan hanya header klien).
 * - Foto diperkecil di server (sisi terpanjang 1600 px, JPEG 82%) supaya hemat disk.
 */
class SubmissionStorage
{
    public const MAX_BYTES = 5 * 1024 * 1024;

    private const ALLOWED = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'pdf' => ['application/pdf'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'mp4' => ['video/mp4', 'application/octet-stream'],
    ];

    /** @return array{path:string,name:string,mime:string,size:int} */
    public static function store(UploadedFile $file, string $dir): array
    {
        if (! $file->isValid() || $file->getSize() > self::MAX_BYTES) {
            throw ValidationException::withMessages(['file' => 'Berkas gagal diunggah atau lebih dari 5 MB.']);
        }

        $ext = strtolower($file->getClientOriginalExtension());
        $mime = (string) $file->getMimeType();
        if (! isset(self::ALLOWED[$ext]) || ! in_array($mime, self::ALLOWED[$ext], true)) {
            throw ValidationException::withMessages(['file' => 'Tipe berkas tidak diizinkan. Gunakan JPG, PNG, WEBP, PDF, DOCX, atau MP4.']);
        }
        // Berkas yang mengaku gambar/dokumen tapi isinya lain sudah tertolak oleh pemeriksaan MIME di atas.
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) && @getimagesize($file->getRealPath()) === false) {
            throw ValidationException::withMessages(['file' => 'Berkas gambar tidak bisa dibaca.']);
        }

        $name = Str::limit(preg_replace('/[^\p{L}\p{N}._ -]+/u', '', $file->getClientOriginalName()) ?: 'lampiran.'.$ext, 140, '');

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) && function_exists('imagecreatefromstring')) {
            $shrunk = self::shrink($file->getRealPath());
            if ($shrunk !== null) {
                $path = $dir.'/'.Str::random(24).'.jpg';
                Storage::disk('local')->put($path, $shrunk);

                return ['path' => $path, 'name' => preg_replace('/\.[a-z]+$/i', '', $name).'.jpg', 'mime' => 'image/jpeg', 'size' => strlen($shrunk)];
            }
        }

        $path = $dir.'/'.Str::random(24).'.'.$ext;
        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));

        return ['path' => $path, 'name' => $name, 'mime' => $mime, 'size' => (int) $file->getSize()];
    }

    /** Perkecil foto ke sisi terpanjang 1600 px, keluarkan JPEG. Null bila gagal. */
    private static function shrink(string $absPath): ?string
    {
        $raw = @file_get_contents($absPath);
        $img = $raw !== false ? @imagecreatefromstring($raw) : false;
        if (! $img) {
            return null;
        }

        $w = imagesx($img);
        $h = imagesy($img);
        $scale = min(1, 1600 / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($nw, $nh);
        // Latar putih agar PNG transparan tidak menjadi hitam saat diubah ke JPEG.
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
        ob_start();
        imagejpeg($dst, null, 82);
        $out = ob_get_clean();
        imagedestroy($dst);
        imagedestroy($img);

        return is_string($out) && $out !== '' ? $out : null;
    }

    /** Hapus berkas fisik; record di DB diurus pemanggil. */
    public static function forget(?string $path): void
    {
        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }
}
