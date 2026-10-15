<?php

// Katalog fitur TSM Superapp. Card di portal hanya muncul jika user sudah di-plot
// ke fitur tsb (tabel feature_user). "admin" = admin fitur; peran lain = pengguna fitur.
return [
    'features' => [
        'lms' => [
            'name' => 'LMS',
            'full' => 'Learning Management System',
            'desc' => 'Materi, quiz, dan tugas per kelas atau cohort.',
            'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
            'roles' => [
                'admin' => ['label' => 'Admin LMS', 'plan' => ['Mengatur cohort (kelas diambil dari data siswa)', 'Tahun pelajaran', 'Mata pelajaran', 'Plotting mapel untuk guru']],
                'guru' => ['label' => 'Guru', 'plan' => ['Membuat materi', 'Membuat soal / quiz', 'Mengatur siapa yang bisa melihat: satu kelas, satu cohort, atau siswa tertentu']],
                'siswa' => ['label' => 'Siswa', 'plan' => ['Melihat materi dan quiz yang ditujukan untuknya', 'Mengerjakan quiz']],
            ],
        ],
        'pkl' => [
            'name' => 'PKL',
            'full' => 'Praktik Kerja Lapangan',
            'desc' => 'Penempatan industri, presensi, kartu kendali, dan nilai PKL.',
            'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
            'roles' => [
                'admin' => ['label' => 'Admin PKL', 'plan' => ['Data industri / DU-DI', 'Plotting siswa ke tempat PKL', 'Tahun ajaran', 'Plotting pembimbing (guru membimbing siswa siapa saja)']],
                'pembimbing' => ['label' => 'Pembimbing', 'plan' => ['Monitoring siswa bimbingan', 'Kartu kendali siswa', 'Penilaian PKL', 'Melihat presensi siswa']],
                'siswa' => ['label' => 'Siswa', 'plan' => ['Melihat kartu kendali', 'Presensi dengan foto + geotag', 'Timeline PKL', 'Status nilai']],
            ],
        ],
        'ukk' => [
            'name' => 'UKK',
            'full' => 'Uji Kompetensi Keahlian',
            'desc' => 'Jadwal, pos ujian, soal teori, dan penilaian penguji.',
            'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
            'roles' => [
                'admin' => ['label' => 'Admin UKK', 'plan' => ['Plotting penguji: hari & pos', 'Mengatur pos ujian', 'Soal per pos', 'Soal teori', 'Jadwal pelaksanaan']],
                'penguji' => ['label' => 'Penguji', 'plan' => ['Menilai siswa sesuai pos yang di-plot']],
                'siswa' => ['label' => 'Siswa', 'plan' => ['Mengerjakan soal teori', 'Melihat hasil nilai']],
            ],
        ],
        'perpus' => [
            'name' => 'Perpustakaan Digital',
            'full' => 'Perpustakaan Digital',
            'desc' => 'Buku, modul, dan referensi digital jurusan.',
            'icon' => 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z',
            'roles' => [
                'admin' => ['label' => 'Admin Perpustakaan', 'plan' => ['Mengatur konten perpustakaan (buku, modul, kategori)']],
                'pembaca' => ['label' => 'Guru / Siswa', 'plan' => ['Mencari dan membaca konten perpustakaan']],
            ],
        ],
    ],
];
