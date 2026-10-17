@extends('errors.layout', [
    'code' => 500,
    'label' => 'Kesalahan server',
    'heading' => 'Ada gangguan di server kami',
    'message' => 'Permintaan Anda gagal diproses karena kendala teknis. Silakan coba lagi beberapa saat lagi.',
    'primaryUrl' => url('/'),
    'primaryLabel' => 'Kembali ke Beranda',
])
