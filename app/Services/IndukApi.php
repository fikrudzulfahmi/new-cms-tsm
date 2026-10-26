<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klien API Data Induk Siswa (read-only).
 *   GET {url}/kelas             -> {"data":[{id,nama,tingkat,jurusan,jumlah_siswa}]}
 *   GET {url}/siswa/{rombel}    -> {"kelas":{..},"data":[{id,nis,nisn,nama,jk,tgl_lahir,status}]}
 * Header: Authorization: Bearer + X-Api-Token (hosting tertentu membuang Authorization).
 * Tanggal lahir sengaja TIDAK dibaca: tidak dipakai LMS.
 */
class IndukApi
{
    public function configured(): bool
    {
        return filled(config('services.induk.url')) && filled(config('services.induk.token'));
    }

    /** Rombel jurusan yang dikonfigurasi (default TSM). */
    public function kelas(): array
    {
        $jur = (string) config('services.induk.jurusan');
        $rows = collect($this->get('kelas'.($jur !== '' ? '?jurusan='.rawurlencode($jur) : '')))->map(fn ($k) => [
            'id' => (string) ($k['id'] ?? ''),
            'nama' => trim((string) ($k['nama'] ?? '')),
            'tingkat' => isset($k['tingkat']) ? (int) $k['tingkat'] : null,
            'jurusan' => isset($k['jurusan']) ? trim((string) $k['jurusan']) : null,
        ])->filter(fn ($k) => $k['id'] !== '' && $k['nama'] !== '');

        // API memakai LIKE sebagian; saring lagi supaya jurusan lain tidak ikut.
        if ($jur !== '') {
            $rows = $rows->filter(fn ($k) => stripos((string) $k['jurusan'], $jur) !== false || stripos($k['nama'], $jur) !== false);
        }

        return $rows->values()->all();
    }

    public function siswa(string $kelasId): array
    {
        return collect($this->get('siswa/'.rawurlencode($kelasId)))->map(function ($s) {
            $lengkap = trim((string) ($s['nis'] ?? ''));
            $angka = preg_replace('/\D/', '', $lengkap);

            return [
                'external_id' => (string) ($s['id'] ?? ''),
                'username' => substr($angka, 0, 5),           // login siswa: 5 angka pertama NIS
                'nis_full' => $lengkap !== '' ? mb_substr($lengkap, 0, 40) : null,
                'nisn' => preg_replace('/\D/', '', (string) ($s['nisn'] ?? '')) ?: null,
                'name' => trim((string) ($s['nama'] ?? '')),
                'gender' => in_array(strtoupper((string) ($s['jk'] ?? '')), ['L', 'P'], true) ? strtoupper($s['jk']) : null,
                'active' => true,   // API hanya mengembalikan siswa aktif (id_status=1)
            ];
        })->filter(fn ($s) => $s['external_id'] !== '')->values()->all();
    }

    private function get(string $path): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('API Data Induk belum diatur (INDUK_API_URL / INDUK_API_TOKEN di .env).');
        }
        $base = (string) config('services.induk.url');
        if (app()->isProduction() && ! str_starts_with($base, 'https://')) {
            throw new RuntimeException('INDUK_API_URL wajib https:// di produksi.');
        }
        try {
            $res = Http::withToken(config('services.induk.token'))
                ->withHeaders(['X-Api-Token' => config('services.induk.token')])
                ->acceptJson()
                ->timeout(config('services.induk.timeout', 20))
                ->retry(2, 300, throw: false)
                ->get(rtrim(config('services.induk.url'), '/').'/'.$path);
        } catch (\Throwable $e) {
            throw new RuntimeException('Tidak bisa terhubung ke aplikasi Data Induk.');
        }
        if (in_array($res->status(), [401, 403], true)) {
            throw new RuntimeException('Token API Data Induk ditolak (HTTP '.$res->status().').');
        }
        if (! $res->successful()) {
            throw new RuntimeException('API Data Induk error HTTP '.$res->status().'.');
        }
        $data = $res->json('data');
        if (! is_array($data)) {
            throw new RuntimeException('Format respons API Data Induk tidak dikenali.');
        }

        return $data;
    }
}
