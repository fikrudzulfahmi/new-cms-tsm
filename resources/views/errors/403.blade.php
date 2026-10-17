@extends('errors.layout', [
    'code' => 403,
    'label' => 'Akses ditolak',
    'heading' => 'Anda tidak punya izin membuka halaman ini',
    'message' => 'Halaman ini khusus untuk pengelola website. Jika Anda admin, silakan masuk kembali dengan akun yang benar.',
    'primaryUrl' => url('/login'),
    'primaryLabel' => 'Masuk sebagai admin',
    'secondaryUrl' => url('/'),
    'secondaryLabel' => 'Ke Beranda',
])
