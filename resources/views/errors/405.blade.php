@extends('errors.layout', [
    'code' => 405,
    'label' => 'Metode tidak diizinkan',
    'heading' => 'Permintaan ini tidak bisa diproses',
    'message' => 'Halaman menerima jenis permintaan yang berbeda. Kembali ke beranda lalu ulangi dari menu yang tersedia.',
    'primaryUrl' => url('/'),
    'primaryLabel' => 'Kembali ke Beranda',
])
