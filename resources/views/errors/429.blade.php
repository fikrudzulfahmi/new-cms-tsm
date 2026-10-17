@extends('errors.layout', [
    'code' => 429,
    'label' => 'Terlalu banyak permintaan',
    'heading' => 'Mohon tunggu sebentar',
    'message' => 'Anda mengirim terlalu banyak permintaan dalam waktu singkat. Tunggu beberapa menit, lalu coba lagi.',
    'primaryUrl' => url('/'),
    'primaryLabel' => 'Kembali ke Beranda',
])
