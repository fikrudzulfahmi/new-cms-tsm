@extends('errors.layout', [
    'code' => 404,
    'label' => 'Halaman tidak ditemukan',
    'heading' => 'Halaman yang Anda cari tidak ada',
    'message' => 'Alamatnya mungkin salah ketik, atau halaman sudah dipindahkan. Silakan mulai lagi dari beranda atau lihat berita terbaru kami.',
    'primaryUrl' => url('/'),
    'primaryLabel' => 'Kembali ke Beranda',
    'secondaryUrl' => url('/berita'),
    'secondaryLabel' => 'Lihat Berita',
])
