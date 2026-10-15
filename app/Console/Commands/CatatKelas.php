<?php

namespace App\Console\Commands;

use App\Models\IndukKelas;
use App\Models\User;
use App\Services\IndukApi;
use Illuminate\Console\Command;

/**
 * php artisan siswa:catat-kelas
 *
 * Sekali pakai: menandai kelas yang siswanya sudah telanjur ada di Superapp sebagai "sudah ditarik",
 * supaya kelas itu tidak bisa ditarik ulang dan mulai diikuti lewat tombol Sinkronkan.
 */
class CatatKelas extends Command
{
    protected $signature = 'siswa:catat-kelas';

    protected $description = 'Tandai kelas yang siswanya sudah ada sebagai sudah ditarik dari Data Induk';

    public function handle(IndukApi $api): int
    {
        try {
            $list = $api->kelas();
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $ditandai = 0;

        foreach ($list as $k) {
            if (IndukKelas::where('induk_id', $k['id'])->exists()) {
                continue;
            }

            $lokal = User::where('role', 'siswa')->where('kelas', $k['nama'])->get(['id']);
            if ($lokal->isEmpty()) {
                continue;
            }

            User::whereIn('id', $lokal->pluck('id'))->whereNull('kelas_induk_id')->update(['kelas_induk_id' => $k['id']]);

            IndukKelas::create([
                'induk_id' => $k['id'],
                'nama' => $k['nama'],
                'tingkat' => $k['tingkat'],
                'jurusan' => $k['jurusan'],
                'jumlah_induk' => (int) ($k['jumlah_siswa'] ?? 0),
                'jumlah_lokal' => $lokal->count(),
                'ditarik_at' => now(),
                'catatan' => 'Ditandai otomatis dari data yang sudah ada',
            ]);

            $this->line("{$k['nama']}: {$lokal->count()} siswa lokal -> sudah ditarik");
            $ditandai++;
        }

        $this->info($ditandai ? "{$ditandai} kelas ditandai sudah ditarik." : 'Tidak ada kelas baru yang perlu ditandai.');

        return self::SUCCESS;
    }
}
