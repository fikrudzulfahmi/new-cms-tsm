<?php

namespace Database\Seeders;

use App\Models\Teacher;
use App\Models\Facility;
use App\Models\Partner;
use App\Models\Slider;
use Illuminate\Database\Seeder;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // Teachers
        $teachers = [
            ['name' => 'Ahmad Wijaya, S.Pd', 'position' => 'Kepala Jurusan', 'subject' => 'Teori Mesin', 'photo' => null],
            ['name' => 'Budi Santoso, M.Pd', 'position' => 'Guru Senior', 'subject' => 'Kelistrikan Motor', 'photo' => null],
            ['name' => 'Citra Dewi, S.Pd', 'position' => 'Guru Praktik', 'subject' => 'Perbengkelan Motor', 'photo' => null],
            ['name' => 'Doni Prasetyo, S.Pd', 'position' => 'Guru Teori', 'subject' => 'Sistem Injeksi', 'photo' => null],
        ];

        foreach ($teachers as $data) {
            Teacher::create($data);
        }

        // Facilities
        $facilities = [
            ['name' => 'Workshop Motor', 'category' => 'Ruang Praktik', 'image' => null],
            ['name' => 'Ruang Simulasi Injeksi', 'category' => 'Laboratorium', 'image' => null],
            ['name' => 'Bengkel Oto', 'category' => 'Fasilitas Bengkel', 'image' => null],
            ['name' => 'Perpustakaan Teknik', 'category' => 'Penunjang', 'image' => null],
            ['name' => 'Ruang CAD/CAM', 'category' => 'Laboratorium', 'image' => null],
            ['name' => 'Aula Serbaguna', 'category' => 'Fasilitas Umum', 'image' => null],
        ];

        foreach ($facilities as $data) {
            Facility::create($data);
        }

        // Partners
        $partners = [
            ['name' => 'PT. Astra International', 'logo' => null],
            ['name' => 'Yamaha Indonesia', 'logo' => null],
            ['name' => 'Honda Manufacturing', 'logo' => null],
            ['name' => 'Suzuki Indonesia', 'logo' => null],
            ['name' => 'PT. Kendaraan Umum', 'logo' => null],
        ];

        foreach ($partners as $data) {
            Partner::create($data);
        }

        // Sliders (if less than 3)
        if (Slider::count() < 3) {
            $sliders = [
                ['title' => 'Teknik Sepeda Motor Modern', 'subtitle' => 'Belajar dengan teknologi terkini dan guru berpengalaman', 'button_text' => 'Pelajari Lebih', 'button_url' => '/tentang', 'image' => null],
                ['title' => 'Lulusan Siap Kerja', 'subtitle' => 'Bergabung dengan ribuan alumni sukses di industri otomotif', 'button_text' => 'Lihat Prestasi', 'button_url' => '/prestasi', 'image' => null],
            ];

            foreach ($sliders as $data) {
                Slider::create($data);
            }
        }
    }
}
