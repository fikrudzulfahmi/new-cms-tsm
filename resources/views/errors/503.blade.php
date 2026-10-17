@extends('errors.layout', [
    'code' => 503,
    'label' => 'Sedang pemeliharaan',
    'heading' => 'Website sedang diperbarui',
    'message' => 'Kami sedang melakukan pemeliharaan singkat. Silakan kembali beberapa menit lagi — terima kasih atas kesabarannya.',
    'primaryUrl' => url('/'),
    'primaryLabel' => 'Coba lagi',
])
