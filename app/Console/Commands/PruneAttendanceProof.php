<?php

namespace App\Console\Commands;

use App\Models\AttendanceRecord;
use App\Services\AttendanceProof;
use Illuminate\Console\Command;

class PruneAttendanceProof extends Command
{
    protected $signature = 'attendance:prune-proof {--days=180 : Umur selfie & lokasi (hari), setara 1 semester}';

    protected $description = 'Hapus selfie dan koordinat presensi yang lebih tua dari 1 semester (status kehadiran tetap).';

    public function handle(): int
    {
        $cut = now()->subDays((int) $this->option('days'));
        $n = 0;
        AttendanceRecord::where('marked_at', '<', $cut)->where(fn ($q) => $q->whereNotNull('photo_path')->orWhereNotNull('lat'))
            ->chunkById(200, function ($rows) use (&$n) {
                foreach ($rows as $r) {
                    AttendanceProof::forget($r->photo_path);
                    $r->update(['photo_path' => null, 'lat' => null, 'lng' => null, 'accuracy' => null]);
                    $n++;
                }
            });
        $this->info("Dibersihkan: {$n} catatan.");

        return self::SUCCESS;
    }
}
