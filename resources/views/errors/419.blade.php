@extends('errors.layout', [
    'code' => 419,
    'label' => 'Sesi berakhir',
    'heading' => 'Halaman terlalu lama terbuka',
    'message' => 'Demi keamanan, sesi Anda sudah berakhir. Muat ulang halaman lalu masuk kembali untuk melanjutkan.',
    'primaryUrl' => url('/login'),
    'primaryLabel' => 'Masuk lagi',
    'secondaryUrl' => url('/'),
    'secondaryLabel' => 'Ke Beranda',
])
