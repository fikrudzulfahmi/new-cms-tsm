<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

class PruneActivityLogs extends Command
{
    protected $signature = 'activity:prune {--days= : Umur maksimal log (hari)}';

    protected $description = 'Hapus log aktivitas yang lebih lama dari batas retensi';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('activity.retention_days', 90));
        if ($days < 7) {
            $this->error('Batas retensi minimal 7 hari.');
            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);
        $total = 0;
        do {
            $deleted = ActivityLog::where('created_at', '<', $cutoff)->limit(5000)->delete();
            $total += $deleted;
        } while ($deleted > 0);

        $this->info("{$total} log lebih lama dari {$days} hari dihapus.");
        return self::SUCCESS;
    }
}
