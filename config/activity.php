<?php

return [
    // Log lebih lama dari ini dihapus otomatis oleh `php artisan activity:prune`.
    'retention_days' => (int) env('ACTIVITY_LOG_RETENTION_DAYS', 90),

    // Ambang "perlu diperiksa": jumlah login gagal dari satu IP dalam 24 jam.
    'failed_login_threshold' => (int) env('ACTIVITY_FAILED_LOGIN_THRESHOLD', 5),
];
