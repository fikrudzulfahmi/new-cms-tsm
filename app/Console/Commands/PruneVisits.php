<?php

namespace App\Console\Commands;

use App\Models\Visit;
use Illuminate\Console\Command;

class PruneVisits extends Command
{
    protected $signature = 'visits:prune {--days=180 : Simpan data kunjungan selama N hari terakhir}';

    protected $description = 'Hapus data kunjungan lama agar tabel visits tidak membengkak';

    public function handle(): int
    {
        $days = max(30, (int) $this->option('days'));
        $deleted = Visit::where('visited_on', '<', now()->subDays($days)->toDateString())->delete();

        $this->info($deleted.' baris kunjungan lebih lama dari '.$days.' hari dihapus.');

        return self::SUCCESS;
    }
}
