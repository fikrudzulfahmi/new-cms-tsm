@extends('errors.layout', [
    'code' => 400,
    'label' => 'Permintaan tidak valid',
    'heading' => 'Permintaan tidak dapat diproses',
    'message' => 'Ada bagian dari permintaan yang tidak sesuai. Kembali ke beranda dan coba lagi dari menu yang tersedia.',
    'primaryUrl' => url('/'),
    'primaryLabel' => 'Kembali ke Beranda',
])
