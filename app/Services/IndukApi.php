<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klien API aplikasi Data Induk Siswa (read-only).
 *   GET {url}/kelas              -> {"data":[{"id":..,"nama":"XI TSM 1","tingkat":11,"jurusan":"TSM","jumlah_siswa":32}]}
 *   GET {url}/siswa/{id_rombel}  -> {"kelas":{..},"data":[{"id":..,"nis":"..","nisn":"..","nama":"..","jk":"L","tgl_lahir":"2009-05-21","status":"aktif"}]}
 * NIS di aplikasi induk berbentuk "31577/1582/004"; yang dipakai siswa untuk login adalah
 * 5 angka pertamanya ("31577", sesuai kartu siswa). Bentuk lengkap disimpan di nis_lengkap.
 * Header: Authorization: Bearer {INDUK_API_TOKEN} (+ X-Api-Token, karena Apache/cPanel kadang membuang header Authorization)
 * Repo aplikasi induk: github.com/fikrudzulfahmi/induk-smekisa (PHP native MVC) - file API ada di docs/induk-api/
 */
class IndukApi
{
    public function configured(): bool
    {
        return filled(config('services.induk.url')) && filled(config('services.induk.token'));
    }

    public function kelas(): array
    {
        $jur = config('services.induk.jurusan');
        return collect($this->get('kelas' . ($jur ? '?jurusan=' . rawurlencode($jur) : '')))->map(fn ($k) => [
            'id' => (string) ($k['id'] ?? ''),
            'nama' => trim((string) ($k['nama'] ?? $k['nama_kelas'] ?? '')),
            'tingkat' => $k['tingkat'] ?? null,
            'jurusan' => $k['jurusan'] ?? null,
            'jumlah_siswa' => $k['jumlah_siswa'] ?? null,
        ])->filter(fn ($k) => $k['id'] !== '' && $k['nama'] !== '')->values()->all();
    }

    public function siswa(string $kelasId): array
    {
        return collect($this->get('siswa/' . rawurlencode($kelasId)))->map(function ($s) {
            $lengkap = trim((string) ($s['nis'] ?? ''));
            $angka = preg_replace('/\D/', '', $lengkap);

            return [
                'induk_id' => (string) ($s['id'] ?? ''),
                'nis' => substr($angka, 0, 5), // kunci login siswa: 5 angka pertama
                'nis_lengkap' => ($lengkap !== '' && $lengkap !== $angka) ? $lengkap : null,
                'nisn' => preg_replace('/\D/', '', (string) ($s['nisn'] ?? '')) ?: null,
                'name' => trim((string) ($s['nama'] ?? '')),
                'gender' => in_array(strtoupper((string) ($s['jk'] ?? '')), ['L', 'P'], true) ? strtoupper($s['jk']) : null,
                'birth_date' => self::date($s['tgl_lahir'] ?? null),
                'status' => strtolower((string) ($s['status'] ?? 'aktif')),
            ];
        })->filter(fn ($s) => $s['nis'] !== '')->values()->all();
    }

    private function get(string $path): array
    {
        if (!$this->configured()) throw new RuntimeException('API Data Induk belum diatur (INDUK_API_URL / INDUK_API_TOKEN di .env).');
        try {
            $res = Http::withToken(config('services.induk.token'))->withHeaders(['X-Api-Token' => config('services.induk.token')])->acceptJson()
                ->timeout(config('services.induk.timeout', 20))->retry(2, 300, throw: false)
                ->get(rtrim(config('services.induk.url'), '/') . '/' . $path);
        } catch (\Throwable $e) {
            throw new RuntimeException('Tidak bisa terhubung ke aplikasi Data Induk: ' . $e->getMessage());
        }
        if ($res->status() === 401 || $res->status() === 403) throw new RuntimeException('Token API Data Induk ditolak (HTTP ' . $res->status() . ').');
        if (!$res->successful()) throw new RuntimeException('API Data Induk error HTTP ' . $res->status() . '.');
        $data = $res->json('data');
        if (!is_array($data)) throw new RuntimeException('Format respons API Data Induk tidak dikenali (butuh field "data" berupa array).');
        return $data;
    }

    /** Terima "2009-05-21", "21-05-2009", "21/05/2009". */
    public static function date($v): ?string
    {
        $v = trim((string) $v);
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $v, $m)) [$y, $mo, $d] = [$m[1], $m[2], $m[3]];
        elseif (preg_match('#^(\d{1,2})[-/.](\d{1,2})[-/.](\d{4})$#', $v, $m)) [$d, $mo, $y] = [$m[1], $m[2], $m[3]];
        else return null;
        return checkdate((int) $mo, (int) $d, (int) $y) ? sprintf('%04d-%02d-%02d', $y, $mo, $d) : null;
    }
}
