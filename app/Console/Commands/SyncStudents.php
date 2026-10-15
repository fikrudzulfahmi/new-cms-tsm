<?php

namespace App\Console\Commands;

use App\Services\SyncSiswa;
use Illuminate\Console\Command;

// php artisan siswa:sync  -> selaraskan semua kelas yang sudah ditarik dengan aplikasi Data Induk
class SyncStudents extends Command
{
    protected $signature = 'siswa:sync';

    protected $description = 'Sinkronkan data siswa dari aplikasi Data Induk (kelas yang sudah ditarik)';

    public function handle(SyncSiswa $sync): int
    {
        $hasil = $sync->semua();
        if ($hasil === []) {
            $this->warn('Belum ada kelas yang ditarik dari Data Induk.');

            return self::SUCCESS;
        }

        foreach ($hasil as $nama => $c) {
            if (isset($c['error'])) {
                $this->error("{$nama}: {$c['error']}");

                continue;
            }
            $this->line(SyncSiswa::pesan($nama, $c));
        }

        return self::SUCCESS;
    }
}
