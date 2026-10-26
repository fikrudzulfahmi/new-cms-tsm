<?php

namespace App\Console\Commands;

use App\Services\SyncInduk as Service;
use Illuminate\Console\Command;

class SyncInduk extends Command
{
    protected $signature = 'induk:sync';

    protected $description = 'Sinkronkan rombel dan siswa TSM dari aplikasi Data Induk';

    public function handle(Service $sync): int
    {
        $run = $sync->run('jadwal');
        $this->line(sprintf('%s: %d rombel, %d baru, %d berubah, %d nonaktif, %d bentrok. %s',
            $run->status, $run->classes, $run->created, $run->updated, $run->deactivated, $run->conflicts, $run->message ?? ''));

        return $run->status === 'failed' ? self::FAILURE : self::SUCCESS;
    }
}
