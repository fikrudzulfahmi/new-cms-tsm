<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Publikasikan berita terjadwal tepat saat waktunya tiba.
Schedule::command('posts:publish-scheduled')->everyMinute()->withoutOverlapping();

// Jaga tabel kunjungan tetap ringan.
Schedule::command('visits:prune')->dailyAt('02:30');

// Log aktivitas disimpan terbatas (config/activity.php), dibersihkan dini hari.
Schedule::command('activity:prune')->dailyAt('03:00');

// Sinkron Data Induk (rombel + siswa TSM) tiap dini hari.
Schedule::command('induk:sync')->dailyAt('02:00')->withoutOverlapping();

// Kumpulkan otomatis quiz yang waktunya habis.
Schedule::command('quiz:finalize')->everyMinute()->withoutOverlapping();
