<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            'name' => 'Administrator', 'email' => 'admin@tsm-blitar.sch.id',
            'email_verified_at' => now(), 'role' => 'admin',
            'password' => Hash::make('Admin#TSM2026'),
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $settings = [
            ['key'=>'site_name','value'=>'TSM SMKS Islam 1 Blitar','group'=>'umum','type'=>'text','label'=>'Nama Situs'],
            ['key'=>'site_tagline','value'=>'Unggul, Berkarakter, dan Siap Kerja','group'=>'umum','type'=>'text','label'=>'Tagline'],
            ['key'=>'site_logo','value'=>'','group'=>'umum','type'=>'image','label'=>'Logo Situs'],
            ['key'=>'site_favicon','value'=>'','group'=>'umum','type'=>'image','label'=>'Favicon'],
            ['key'=>'school_name','value'=>'SMKS Islam 1 Kota Blitar','group'=>'profil','type'=>'text','label'=>'Nama Sekolah'],
            ['key'=>'department_name','value'=>'Teknik Sepeda Motor','group'=>'profil','type'=>'text','label'=>'Nama Jurusan'],
            ['key'=>'address','value'=>'Jl. Musi No. 06 Kota Blitar','group'=>'profil','type'=>'textarea','label'=>'Alamat'],
            ['key'=>'phone','value'=>'(0342) 801234','group'=>'profil','type'=>'text','label'=>'Telepon'],
            ['key'=>'email','value'=>'info@tsm-blitar.sch.id','group'=>'profil','type'=>'text','label'=>'Email'],
            ['key'=>'maps_embed','value'=>'','group'=>'profil','type'=>'textarea','label'=>'Google Maps Embed'],
            ['key'=>'facebook_url','value'=>'','group'=>'sosmed','type'=>'text','label'=>'Facebook URL'],
            ['key'=>'instagram_url','value'=>'','group'=>'sosmed','type'=>'text','label'=>'Instagram URL'],
            ['key'=>'youtube_url','value'=>'','group'=>'sosmed','type'=>'text','label'=>'YouTube URL'],
            ['key'=>'tiktok_url','value'=>'','group'=>'sosmed','type'=>'text','label'=>'TikTok URL'],
            ['key'=>'hero_title','value'=>'Jurusan Teknik Sepeda Motor','group'=>'beranda','type'=>'text','label'=>'Hero Judul'],
            ['key'=>'hero_subtitle','value'=>'SMKS Islam 1 Kota Blitar — Mencetak Teknisi Handal Berkarakter Islami','group'=>'beranda','type'=>'textarea','label'=>'Hero Subjudul'],
            ['key'=>'hero_bg','value'=>'','group'=>'beranda','type'=>'image','label'=>'Hero Background'],
            ['key'=>'about_text','value'=>'Jurusan Teknik Sepeda Motor SMKS Islam 1 Kota Blitar hadir untuk mencetak tenaga teknisi sepeda motor yang kompeten, berkarakter Islami, dan siap memasuki dunia industri.','group'=>'beranda','type'=>'html','label'=>'Teks Tentang Kami'],
            ['key'=>'about_image','value'=>'','group'=>'beranda','type'=>'image','label'=>'Foto Tentang Kami'],
            ['key'=>'ppdb_open','value'=>'1','group'=>'ppdb','type'=>'boolean','label'=>'SPMB Dibuka'],
            ['key'=>'ppdb_info','value'=>'Pendaftaran SPMB (Sistem Penerimaan Murid Baru) Tahun Pelajaran 2026/2027 sudah dibuka!','group'=>'ppdb','type'=>'textarea','label'=>'Info SPMB'],
            ['key'=>'footer_text','value'=>'© 2026 Teknik Sepeda Motor SMKS Islam 1 Kota Blitar. Semua hak dilindungi.','group'=>'umum','type'=>'textarea','label'=>'Teks Footer'],
        ];
        foreach ($settings as $s) {
            DB::table('settings')->insert(array_merge($s, ['description'=>null,'created_at'=>now(),'updated_at'=>now()]));
        }

        $categories = [
            ['name'=>'Berita','slug'=>'berita','description'=>'Berita terbaru jurusan TSM','color'=>'#1e3a8a','sort_order'=>0],
            ['name'=>'Pengumuman','slug'=>'pengumuman','description'=>'Pengumuman penting','color'=>'#dc2626','sort_order'=>1],
            ['name'=>'Pengetahuan','slug'=>'pengetahuan','description'=>'Artikel pengetahuan otomotif','color'=>'#059669','sort_order'=>2],
            ['name'=>'Kegiatan','slug'=>'kegiatan','description'=>'Kegiatan siswa dan sekolah','color'=>'#7c3aed','sort_order'=>3],
            ['name'=>'Prestasi','slug'=>'prestasi','description'=>'Prestasi siswa dan sekolah','color'=>'#d97706','sort_order'=>4],
        ];
        foreach ($categories as $c) {
            DB::table('categories')->insert(array_merge($c, ['is_active'=>true,'created_at'=>now(),'updated_at'=>now()]));
        }

        $competencies = [
            ['title'=>'Perawatan & Perbaikan Mesin','slug'=>'perawatan-perbaikan-mesin','icon'=>'wrench-screwdriver','short_description'=>'Melakukan tune up, ganti oli, dan perbaikan mesin sepeda motor.','sort_order'=>1],
            ['title'=>'Sistem Kelistrikan Sepeda Motor','slug'=>'sistem-kelistrikan','icon'=>'bolt','short_description'=>'Memahami dan memperbaiki sistem pengisian, starter, dan lampu.','sort_order'=>2],
            ['title'=>'Sistem Injeksi & EFI','slug'=>'sistem-injeksi-efi','icon'=>'cpu-chip','short_description'=>'Diagnosa dan perbaikan sistem bahan bakar injeksi elektronik.','sort_order'=>3],
            ['title'=>'Sistem Rem & Suspensi','slug'=>'sistem-rem-suspensi','icon'=>'shield-check','short_description'=>'Perawatan sistem rem cakram, tromol, dan suspensi.','sort_order'=>4],
        ];
        foreach ($competencies as $c) {
            DB::table('competencies')->insert(array_merge($c, ['description'=>null,'image'=>null,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]));
        }

        $programs = [
            ['title'=>'Teaching Factory','subtitle'=>'Belajar Sambil Berpenghasilan','icon'=>'building-storefront','description'=>'Siswa mengerjakan servis sepeda motor pelanggan nyata di bengkel sekolah.','sort_order'=>1],
            ['title'=>'Praktek Kerja Industri','subtitle'=>'Magang di Bengkel Mitra','icon'=>'briefcase','description'=>'Siswa kelas XI magang 6 bulan di bengkel resmi Yamaha, Honda, dan Suzuki.','sort_order'=>2],
            ['title'=>'Sertifikasi Kompetensi','subtitle'=>'LSP-P1 Berlisensi BNSP','icon'=>'document-check','description'=>'Lulusan mendapat Sertifikat Kompetensi BNSP yang diakui industri nasional.','sort_order'=>3],
            ['title'=>'Bursa Kerja Khusus','subtitle'=>'Penempatan Kerja Langsung','icon'=>'users','description'=>'BKK aktif menjalin kerjasama dengan lebih dari 20 perusahaan otomotif.','sort_order'=>4],
        ];
        foreach ($programs as $p) {
            DB::table('programs')->insert(array_merge($p, ['image'=>null,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]));
        }

        $sliders = [
            ['title'=>'Selamat Datang di Jurusan TSM','subtitle'=>'Mencetak Teknisi Handal Berkarakter Islami','button_text'=>'Pelajari Lebih Lanjut','button_url'=>'#tentang','sort_order'=>1],
            ['title'=>'SPMB 2026/2027 Dibuka!','subtitle'=>'Daftar sekarang dan raih masa depan cerah bersama kami','button_text'=>'Daftar SPMB','button_url'=>'/spmb','sort_order'=>2],
        ];
        foreach ($sliders as $s) {
            DB::table('sliders')->insert(array_merge($s, ['image'=>null,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]));
        }

        $catId = DB::table('categories')->where('slug','berita')->value('id');
        $userId = DB::table('users')->where('email','admin@tsm-blitar.sch.id')->value('id');
        DB::table('posts')->insert([
            'category_id'=>$catId,'user_id'=>$userId,
            'title'=>'Selamat Datang di Website Resmi Jurusan TSM',
            'slug'=>'selamat-datang-di-website-resmi-jurusan-tsm',
            'excerpt'=>'Website resmi jurusan Teknik Sepeda Motor SMKS Islam 1 Kota Blitar kini hadir untuk memudahkan informasi.',
            'body'=>'<p>Website resmi jurusan <strong>Teknik Sepeda Motor (TSM)</strong> SMKS Islam 1 Kota Blitar telah resmi diluncurkan!</p><p>Melalui website ini, kami menyediakan informasi lengkap tentang jurusan, program unggulan, prestasi siswa, dan berita terkini.</p>',
            'status'=>'published','published_at'=>now(),'is_featured'=>true,'allow_comments'=>true,
            'views_count'=>0,'likes_count'=>0,'dislikes_count'=>0,'shares_count'=>0,'comments_count'=>0,'reading_time'=>2,
            'meta_title'=>'Selamat Datang di Website Resmi Jurusan TSM - SMKS Islam 1 Blitar',
            'meta_description'=>'Website resmi jurusan Teknik Sepeda Motor SMKS Islam 1 Kota Blitar.',
            'focus_keyword'=>'teknik sepeda motor blitar','seo_score'=>80,'seo_report'=>null,
            'og_image'=>null,'canonical_url'=>null,'cover_image'=>null,'cover_alt'=>null,
            'created_at'=>now(),'updated_at'=>now(),
        ]);
    }
}
