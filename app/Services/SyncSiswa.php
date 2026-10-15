<?php

namespace App\Services;

use App\Models\IndukKelas;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Menyelaraskan akun siswa di Superapp dengan aplikasi Data Induk:
 *  - siswa baru         -> dibuatkan akun (nonaktif bila status induk bukan aktif)
 *  - data berubah       -> nama, NIS/NISN, jenis kelamin, tanggal lahir, kelas diperbarui
 *  - tidak ada di induk -> akun dinonaktifkan (tidak dihapus, agar riwayat/plotting tetap ada)
 * Tidak pernah menghapus akun dan tidak pernah mengubah apa pun di aplikasi induk.
 */
class SyncSiswa
{
    public function __construct(private IndukApi $api) {}

    /**
     * Tarik pertama kali: hanya NIS terpilih yang disimpan.
     *
     * @param  array<int, string>  $hanyaNis  5 angka pertama NIS yang dicentang di pratinjau
     */
    public function impor(IndukKelas $kelas, array $hanyaNis): array
    {
        return $this->terapkan($kelas, $this->api->siswa($kelas->induk_id), $hanyaNis, false);
    }

    /** Sinkron satu kelas: seluruh isi kelas mengikuti Data Induk. */
    public function kelas(IndukKelas $kelas): array
    {
        return $this->terapkan($kelas, $this->api->siswa($kelas->induk_id), null, true);
    }

    /**
     * Sinkron semua kelas yang sudah ditarik.
     *
     * @return array<string, array<string, mixed>> nama kelas => hasil (atau ['error' => pesan])
     */
    public function semua(): array
    {
        $hasil = [];
        foreach (IndukKelas::orderBy('nama')->get() as $kelas) {
            try {
                $hasil[$kelas->nama] = $this->kelas($kelas);
            } catch (RuntimeException $e) {
                $hasil[$kelas->nama] = ['error' => $e->getMessage()];
            }
        }

        return $hasil;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows   hasil IndukApi::siswa()
     * @param  array<int, string>|null  $hanyaNis       null = semua siswa kelas ini
     * @param  bool  $tandaiKeluar                      nonaktifkan siswa lokal yang hilang dari induk
     */
    private function terapkan(IndukKelas $kelas, array $rows, ?array $hanyaNis, bool $tandaiKeluar): array
    {
        $c = ['baru' => 0, 'diperbarui' => 0, 'sama' => 0, 'dilewati' => 0, 'nonaktif' => 0, 'keluar' => 0];

        // 5 angka pertama NIS sebagai kunci; NIS ganda di data induk tidak dipakai (dilewati).
        $peta = [];
        $ganda = [];
        foreach ($rows as $s) {
            $kunci = (string) ($s['nis'] ?? '');
            if ($kunci === '') {
                continue;
            }
            if (isset($peta[$kunci])) {
                $ganda[$kunci] = true;
            }
            $peta[$kunci] = $s;
        }

        DB::transaction(function () use ($kelas, $peta, $ganda, $hanyaNis, $tandaiKeluar, &$c) {
            foreach ($peta as $kunci => $s) {
                if (isset($ganda[$kunci]) || $s['name'] === '' || !$s['birth_date']) {
                    $c['dilewati']++;
                    continue;
                }
                if ($hanyaNis !== null && !in_array($kunci, $hanyaNis, true)) {
                    continue;
                }

                $u = User::where('role', 'siswa')->where('nis', $kunci)->first();
                if ($u === null) {
                    $u = User::where('role', 'siswa')->where('nis_lengkap', $s['nis_lengkap'] ?: $kunci)->first();
                }

                $aktif = ($s['status'] ?? 'aktif') === 'aktif';
                $attrs = [
                    'name' => $s['name'],
                    'nis' => $kunci,
                    'nis_lengkap' => $s['nis_lengkap'],
                    'nisn' => $s['nisn'],
                    'gender' => $s['gender'],
                    'birth_date' => $s['birth_date'],
                    'kelas' => $kelas->nama,
                    'kelas_induk_id' => $kelas->induk_id,
                    'induk_id' => ($s['induk_id'] ?? '') !== '' ? $s['induk_id'] : null,
                    'synced_at' => now(),
                ];

                if ($u === null) {
                    User::create($attrs + [
                        'nis' => $kunci,
                        'role' => 'siswa',
                        'email' => null,
                        // siswa login memakai NIS + tanggal lahir, password hanya pengisi kolom
                        'password' => Str::random(40),
                        'is_active' => $aktif,
                    ]);
                    $c['baru']++;

                    continue;
                }

                if ($u->role !== 'siswa') {
                    $c['dilewati']++;

                    continue;
                }

                if (!$aktif && $u->is_active) {
                    $u->fill($attrs + ['is_active' => false])->save();
                    $c['nonaktif']++;

                    continue;
                }

                $berubah = $u->name !== $attrs['name']
                    || $u->nis_lengkap !== $attrs['nis_lengkap']
                    || $u->nisn !== $attrs['nisn']
                    || $u->gender !== $attrs['gender']
                    || $u->kelas !== $attrs['kelas']
                    || $u->kelas_induk_id !== $attrs['kelas_induk_id']
                    || (string) $u->induk_id !== (string) $attrs['induk_id']
                    || $u->birth_date?->format('Y-m-d') !== $attrs['birth_date'];

                if (!$u->is_active && $aktif) {
                    $u->fill($attrs + ['is_active' => true])->save();
                    $c['diperbarui']++;

                    continue;
                }

                if ($berubah) {
                    $u->fill($attrs)->save();
                    $c['diperbarui']++;
                } else {
                    $u->forceFill(['synced_at' => now()])->save();
                    $c['sama']++;
                }
            }

            if ($tandaiKeluar) {
                $kunciApi = array_keys($peta) ?: [''];
                $hilang = User::where('role', 'siswa')
                    ->where('kelas_induk_id', $kelas->induk_id)
                    ->whereNotIn('nis', $kunciApi)
                    ->where('is_active', true)
                    ->get();
                foreach ($hilang as $u) {
                    $u->forceFill(['is_active' => false, 'synced_at' => now()])->save();
                    $c['keluar']++;
                }
            }
        });

        $kelas->fill([
            'jumlah_induk' => count($peta),
            'jumlah_lokal' => User::where('role', 'siswa')->where('kelas_induk_id', $kelas->induk_id)->count(),
            'disinkron_at' => now(),
            'catatan' => sprintf('%d baru · %d diperbarui · %d nonaktif · %d keluar', $c['baru'], $c['diperbarui'], $c['nonaktif'], $c['keluar']),
        ])->save();

        return $c;
    }

    /** Kalimat ringkas hasil sinkron untuk pesan ke pengguna. */
    public static function pesan(string $namaKelas, array $c): string
    {
        $bagian = [];
        if ($c['baru']) {
            $bagian[] = "{$c['baru']} siswa baru";
        }
        if ($c['diperbarui']) {
            $bagian[] = "{$c['diperbarui']} diperbarui";
        }
        if ($c['sama']) {
            $bagian[] = "{$c['sama']} tanpa perubahan";
        }
        if ($c['nonaktif']) {
            $bagian[] = "{$c['nonaktif']} dinonaktifkan (status induk bukan aktif)";
        }
        if ($c['keluar']) {
            $bagian[] = "{$c['keluar']} dinonaktifkan (tidak ada lagi di kelas ini)";
        }
        if ($c['dilewati']) {
            $bagian[] = "{$c['dilewati']} dilewati (data belum lengkap)";
        }

        return "Kelas {$namaKelas}: " . (implode(', ', $bagian) ?: 'tidak ada siswa') . '.';
    }
}
