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
