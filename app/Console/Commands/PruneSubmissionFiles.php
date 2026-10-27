<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\Submission;
use App\Services\SubmissionStorage;
use Illuminate\Console\Command;

class PruneSubmissionFiles extends Command
{
    protected $signature = 'assignments:prune-files {--days= : Umur minimal (hari) sejak dinilai; 0 = mati}';

    protected $description = 'Hapus lampiran tugas lama yang sudah dinilai (nilai & feedback tetap).';

    public function handle(): int
    {
        $days = $this->option('days');
        $days = $days !== null ? (int) $days : (int) Setting::get('lms.assignment_file_days', 180);
        if ($days <= 0) {
            $this->info('Pembersihan otomatis dimatikan (retensi 0).');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);
        $n = 0;
        Submission::whereNotNull('graded_at')->where('graded_at', '<', $cutoff)
            ->where(fn ($q) => $q->whereNotNull('file_path')->orWhereHas('files'))
            ->chunkById(200, function ($subs) use (&$n) {
                foreach ($subs as $sub) {
                    SubmissionStorage::forget($sub->file_path);
                    foreach ($sub->files as $f) {
                        SubmissionStorage::forget($f->path);
                    }
                    $sub->files()->delete();
                    $sub->update(['file_path' => null, 'file_name' => null, 'file_mime' => null, 'file_size' => null]);
                    $n++;
                }
            });

        ActivityLog::record('storage.prune', 'lms', 'Pembersihan otomatis lampiran tugas: '.$n.' pengumpulan', null, ['hari' => $days]);
        $this->info("Selesai. {$n} pengumpulan dibersihkan.");

        return self::SUCCESS;
    }
}
