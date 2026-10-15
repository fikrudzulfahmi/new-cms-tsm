<?php

namespace Database\Seeders;

use App\Models\{Achievement, Category, Comment, ContactMessage, Competency, Facility, Gallery, Partner, Post, PostReaction, Program, Registration, Slider, Tag, Teacher, Testimonial, User, Visit};
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Data dummy untuk pratinjau situs. Jalankan: php artisan db:seed --class=DemoContentSeeder
 * Aman diulang: data demo lama (penanda "[DEMO]" / folder dummy/) dihapus dulu.
 */
class DemoContentSeeder extends Seeder
{
    private array $palette = [[10, 26, 58], [18, 40, 90], [30, 79, 168], [74, 93, 128]];

    private function img(string $name, string $label, int $w = 1200, int $h = 675, int $variant = 0): string
    {
        $path = "dummy/{$name}.jpg";
        $im = imagecreatetruecolor($w, $h);
        [$r, $g, $b] = $this->palette[$variant % count($this->palette)];
        imagefill($im, 0, 0, imagecolorallocate($im, $r, $g, $b));
        $grid = imagecolorallocatealpha($im, 124, 200, 255, 105);
        for ($x = 0; $x < $w; $x += 40) imageline($im, $x, 0, $x, $h, $grid);
        for ($y = 0; $y < $h; $y += 40) imageline($im, 0, $y, $w, $y, $grid);
        $accent = imagecolorallocate($im, 124, 200, 255);
        imagesetthickness($im, 3);
        $cx = (int) ($w * 0.78); $cy = (int) ($h * 0.5); $rad = (int) (min($w, $h) * 0.28);
        imageellipse($im, $cx, $cy, $rad * 2, $rad * 2, $accent);
        imageellipse($im, $cx, $cy, (int) ($rad * 0.7), (int) ($rad * 0.7), $accent);
        for ($i = 0; $i < 12; $i++) {
            $a = deg2rad($i * 30);
            imageline($im, (int) ($cx + cos($a) * $rad), (int) ($cy + sin($a) * $rad), (int) ($cx + cos($a) * ($rad + 18)), (int) ($cy + sin($a) * ($rad + 18)), $accent);
        }
        $white = imagecolorallocate($im, 255, 255, 255);
        $font = 5; $scale = max(1, (int) floor($w / 400));
        $txt = strtoupper(Str::limit($label, 34, ''));
        $tw = imagefontwidth($font) * strlen($txt); $th = imagefontheight($font);
        $tmp = imagecreatetruecolor($tw, $th);
        imagefill($tmp, 0, 0, imagecolorallocate($tmp, $r, $g, $b));
        imagestring($tmp, $font, 0, 0, $txt, $white);
        imagecopyresized($im, $tmp, (int) ($w * 0.06), (int) ($h / 2 - $th * $scale / 2), 0, 0, $tw * $scale, $th * $scale, $tw, $th);
        imagestring($im, 3, (int) ($w * 0.06), (int) ($h / 2 + $th * $scale), 'GAMBAR DUMMY - TSM', $accent);
        ob_start(); imagejpeg($im, null, 82); $data = ob_get_clean();
        imagedestroy($im); imagedestroy($tmp);
        Storage::disk('public')->put($path, $data);
        return $path;
    }

    public function run(): void
    {
        Storage::disk('public')->deleteDirectory('dummy');
        $this->cleanup();

        $admin = User::first();
        $cats = Category::pluck('id', 'name');
        if ($cats->isEmpty()) {
            foreach (['Berita Sekolah', 'Prestasi', 'Kegiatan', 'Pengumuman', 'Artikel Otomotif'] as $i => $n) {
                Category::create(['name' => $n, 'slug' => Str::slug($n), 'sort_order' => $i, 'is_active' => true, 'color' => '#1E4FA8']);
            }
            $cats = Category::pluck('id', 'name');
        }

        // ---------- Gambar untuk data yang sudah ada tapi belum bergambar ----------
        foreach (Slider::all() as $i => $s) if (!$s->image) $s->update(['image' => $this->img("slider-$i", $s->title, 1600, 900, $i)]);
        foreach (Teacher::all() as $i => $t) if (!$t->photo) $t->update(['photo' => $this->img("guru-$i", $t->name, 600, 600, $i + 1)]);
        foreach (Partner::all() as $i => $p) if (!$p->logo) $p->update(['logo' => $this->img("mitra-$i", $p->name, 400, 200, $i)]);
        foreach (Facility::all() as $i => $f) if (!$f->image) $f->update(['image' => $this->img("sarana-$i", $f->name, 900, 600, $i)]);
        foreach (Competency::all() as $i => $c) if (!$c->image) $c->update(['image' => $this->img("komp-$i", $c->title, 900, 600, $i + 2)]);
        foreach (Program::all() as $i => $p) if (!$p->image) $p->update(['image' => $this->img("prog-$i", $p->title, 900, 600, $i + 1)]);

        $extraTeachers = [
            ['Drs. H. Imam Syafi\'i, M.Pd.', 'Kepala Sekolah', 'Manajemen Pendidikan', 'S2 Manajemen Pendidikan'],
            ['Agus Prasetyo, S.T.', 'Kaprog TSM', 'Pemeliharaan Mesin Sepeda Motor', 'S1 Teknik Mesin'],
            ['Siti Nurhaliza, S.Pd.', 'Guru Produktif', 'Kelistrikan Sepeda Motor', 'S1 Pendidikan Teknik Otomotif'],
            ['Rizky Ramadhan, S.Pd.', 'Guru Produktif', 'Sasis & Pemindah Tenaga', 'S1 PTO'],
            ['Dewi Lestari, S.Pd.', 'Guru BK', 'Bimbingan Konseling', 'S1 Psikologi Pendidikan'],
            ['Muhammad Fauzan, A.Md.', 'Toolman', 'Bengkel TSM', 'D3 Teknik Mesin'],
        ];
        $base = Teacher::max('sort_order') ?? 0;
        foreach ($extraTeachers as $i => [$n, $pos, $sub, $edu]) {
            Teacher::create(['name' => "$n", 'position' => $pos, 'subject' => $sub, 'education' => $edu, 'email' => Str::slug(explode(',', $n)[0], '.') . '@tsm-blitar.sch.id',
                'bio' => "[DEMO] $pos berpengalaman lebih dari " . (5 + $i * 2) . ' tahun di bidang otomotif roda dua.',
                'photo' => $this->img("guru-x$i", $n, 600, 600, $i), 'sort_order' => $base + $i + 1, 'is_active' => true]);
        }

        // ---------- Tag ----------
        $tags = collect(['Honda', 'Yamaha', 'Injeksi', 'Servis Berkala', 'LKS', 'PKL', 'Prakerin', 'Kewirausahaan', 'Teaching Factory', 'Ekstrakurikuler', 'Ramadhan', 'Alumni'])
            ->map(fn ($t) => Tag::firstOrCreate(['slug' => Str::slug($t)], ['name' => $t]));

        // ---------- Berita ----------
        $yt = ['dQw4w9WgXcQ', 'aqz-KE-bpKQ', 'jNQXAC9IVRw'];
        $posts = [
            ['Siswa TSM SMKS Islam 1 Blitar Juara 1 LKS Kota Blitar 2026', 'Prestasi', 'lks kota blitar'],
            ['Kunjungan Industri ke Pabrik Astra Honda Motor Cikarang', 'Kegiatan', 'kunjungan industri'],
            ['Pendaftaran Peserta Didik Baru TSM Tahun Ajaran 2027/2028 Dibuka', 'Pengumuman', 'ppdb tsm'],
            ['Cara Merawat Motor Injeksi Agar Tetap Irit dan Awet', 'Artikel Otomotif', 'motor injeksi'],
            ['Teaching Factory TSM Layani Servis Motor Warga Sekitar', 'Kegiatan', 'teaching factory'],
            ['Uji Kompetensi Keahlian Kelas XII Berjalan Lancar', 'Berita Sekolah', 'uji kompetensi keahlian'],
            ['Tanda Kampas Rem Motor Harus Segera Diganti', 'Artikel Otomotif', 'kampas rem motor'],
            ['Pondok Ramadhan dan Bakti Sosial Servis Gratis', 'Kegiatan', 'servis gratis'],
            ['Alumni TSM Kini Membuka Bengkel Sendiri di Blitar', 'Berita Sekolah', 'alumni tsm'],
            ['Jadwal Praktik Kerja Lapangan (PKL) Semester Genap', 'Pengumuman', 'praktik kerja lapangan'],
            ['Tim TSM Raih Medali Perak LKS Provinsi Jawa Timur', 'Prestasi', 'lks provinsi'],
            ['Mengenal Sistem Kelistrikan Sepeda Motor untuk Pemula', 'Artikel Otomotif', 'kelistrikan sepeda motor'],
            ['Workshop Kewirausahaan Bengkel Bersama Yamaha', 'Kegiatan', 'kewirausahaan bengkel'],
            ['Draft: Rencana Lomba Modifikasi Motor Antar SMK', 'Kegiatan', 'lomba modifikasi'],
        ];
        $names = ['Budi Santoso', 'Ani Rahmawati', 'Fajar Nugroho', 'Lina Marlina', 'Hendra Wijaya', 'Rina Puspita', 'Yoga Pratama', 'Sari Wulandari', 'Dimas Saputra', 'Putri Ayu'];
        $commentTexts = ['Mantap, semoga makin berprestasi!', 'Bangga dengan TSM SMKS Islam 1 Blitar 👍', 'Informasinya sangat bermanfaat, terima kasih.', 'Apakah tahun ini ada kuota beasiswa?', 'Anak saya alumni sini, sekarang kerja di dealer resmi.', 'Kapan jadwal servis gratis berikutnya?', 'Artikelnya jelas dan mudah dipahami.', 'Sukses terus untuk guru dan siswa TSM!'];

        foreach ($posts as $i => [$title, $cat, $kw]) {
            $catId = $cats[$cat] ?? $cats->first();
            $published = !str_starts_with($title, 'Draft');
            $date = Carbon::now()->subDays($i * 4 + 1)->setTime(8 + $i % 8, 15);
            $body = $this->body($title, $kw, $i, $yt[$i % 3]);
            $post = Post::create([
                'category_id' => $catId, 'user_id' => $admin?->id, 'title' => $title, 'slug' => Str::slug($title),
                'excerpt' => "[DEMO] " . Str::limit("Ulasan lengkap tentang $kw di Jurusan Teknik Sepeda Motor SMKS Islam 1 Blitar, mulai dari latar belakang, pelaksanaan, hingga hasil yang dicapai.", 200),
                'body' => $body, 'cover_image' => $this->img("berita-$i", $title, 1200, 675, $i), 'cover_alt' => $title,
                'status' => $published ? 'published' : 'draft', 'published_at' => $published ? $date : null,
                'is_featured' => $i < 3, 'allow_comments' => true,
                'views_count' => $published ? rand(40, 1500) : 0, 'likes_count' => 0, 'dislikes_count' => 0, 'shares_count' => rand(0, 60),
                'reading_time' => max(1, (int) round(str_word_count(strip_tags($body)) / 200)),
                'meta_title' => Str::limit($title, 60, ''), 'meta_description' => Str::limit("Berita $kw dari Jurusan Teknik Sepeda Motor SMKS Islam 1 Blitar: informasi lengkap, foto kegiatan, dan video dokumentasi.", 156, ''),
                'focus_keyword' => $kw, 'seo_score' => rand(55, 95), 'created_at' => $date, 'updated_at' => $date,
            ]);
            $post->tags()->sync($tags->random(rand(2, 4))->pluck('id'));

            if ($published) {
                $likes = rand(3, 40); $dislikes = rand(0, 4);
                for ($k = 0; $k < $likes + $dislikes; $k++) {
                    PostReaction::create(['post_id' => $post->id, 'visitor_hash' => md5("demo-$i-$k"), 'value' => $k < $likes ? 1 : -1]);
                }
                $cc = 0;
                foreach (range(0, rand(1, 5)) as $k) {
                    $status = $k === 0 && $i % 3 === 0 ? 'pending' : 'approved';
                    $c = Comment::create(['post_id' => $post->id, 'name' => $names[($i + $k) % 10], 'email' => 'demo' . $k . '@contoh.id',
                        'body' => $commentTexts[($i + $k) % count($commentTexts)], 'status' => $status, 'ip_hash' => md5("c$i$k"), 'user_agent' => '[DEMO]']);
                    $c->forceFill(['created_at' => $date->copy()->addHours($k * 5 + 2)])->save();
                    if ($status === 'approved') $cc++;
                    if ($k === 1) {
                        Comment::create(['post_id' => $post->id, 'parent_id' => $c->id, 'name' => 'Admin TSM', 'email' => 'admin@tsm-blitar.sch.id',
                            'body' => 'Terima kasih atas tanggapannya. Info lebih lanjut silakan hubungi sekolah.', 'status' => 'approved', 'ip_hash' => md5("r$i"), 'user_agent' => '[DEMO]']);
                        $cc++;
                    }
                }
                $post->update(['likes_count' => $likes, 'dislikes_count' => $dislikes, 'comments_count' => $cc]);
            }
        }

        // ---------- Prestasi ----------
        $ach = [
            ['Juara 1 LKS Teknologi Sepeda Motor', 'Ahmad Rizal', 'Kota', 'Juara 1', 'Dinas Pendidikan Kota Blitar'],
            ['Medali Perak LKS Jawa Timur', 'Bagus Setiawan', 'Provinsi', 'Juara 2', 'Dinas Pendidikan Provinsi Jatim'],
            ['Finalis LKS Nasional Motorcycle Technology', 'Bagus Setiawan', 'Nasional', 'Finalis', 'Puspresnas Kemendikbudristek'],
            ['Juara 1 Kontes Mekanik Muda Honda', 'Tim TSM', 'Provinsi', 'Juara 1', 'MPM Honda Jatim'],
            ['Juara 3 Lomba Modifikasi Motor Pelajar', 'Fikri Hidayat', 'Kota', 'Juara 3', 'IMI Blitar'],
            ['Best Presentation ASEAN Vocational Expo', 'Tim TSM', 'Internasional', 'Penghargaan', 'SEAMEO TED'],
            ['Juara 2 Yamaha Technician Skill Contest', 'Dimas Arya', 'Nasional', 'Juara 2', 'PT Yamaha Indonesia'],
            ['Juara Umum Pekan Olahraga Pelajar', 'Kelas XI TSM', 'Kota', 'Juara Umum', 'KONI Kota Blitar'],
        ];
        foreach ($ach as $i => [$t, $s, $lv, $rk, $org]) {
            Achievement::create(['title' => $t, 'student_name' => $s, 'level' => $lv, 'rank' => $rk, 'organizer' => $org, 'year' => 2026 - intdiv($i, 3),
                'achieved_at' => Carbon::now()->subMonths($i * 2), 'description' => "[DEMO] $t yang diraih $s, diselenggarakan oleh $org.",
                'image' => $this->img("prestasi-$i", $t, 900, 600, $i), 'is_featured' => $i < 4, 'sort_order' => $i, 'is_active' => true]);
        }

        // ---------- Testimoni ----------
        $testi = [
            ['Arif Budiman', 2019, 'Mekanik Senior', 'AHASS Blitar', 'Ilmu di TSM langsung terpakai di dunia kerja. Sekarang saya dipercaya jadi kepala mekanik.', 'approved'],
            ['Nur Aini', 2020, 'Service Advisor', 'Yamaha Flagship Malang', 'Guru-gurunya sabar dan praktiknya banyak. Perempuan pun bisa sukses di otomotif!', 'approved'],
            ['Wahyu Hidayat', 2018, 'Pemilik Bengkel', 'Wahyu Motor', 'Bekal kewirausahaan dari TSM membuat saya berani buka bengkel sendiri.', 'approved'],
            ['Eko Prasetyo', 2021, 'Teknisi', 'PT Astra Honda Motor', 'Lulus langsung direkrut lewat program BKK sekolah. Terima kasih TSM!', 'approved'],
            ['Rudi Hartono', 2022, 'Mahasiswa', 'Politeknik Negeri Malang', 'Dasar otomotif dari TSM sangat membantu kuliah saya di teknik mesin.', 'approved'],
            ['Tri Wulandari', 2023, 'Admin Dealer', 'Suzuki Kediri', 'Testimoni ini menunggu persetujuan admin (contoh status pending).', 'pending'],
        ];
        foreach ($testi as $i => [$n, $y, $o, $w, $m, $st]) {
            Testimonial::create(['name' => $n, 'graduation_year' => $y, 'occupation' => $o, 'workplace' => $w, 'message' => $m, 'rating' => 5 - ($i % 2),
                'photo' => $this->img("testi-$i", $n, 400, 400, $i), 'status' => $st, 'is_featured' => $i < 3]);
        }

        // ---------- Galeri ----------
        $albums = ['Praktik Bengkel', 'Kunjungan Industri', 'Prestasi', 'Kegiatan Keagamaan'];
        for ($i = 0; $i < 12; $i++) {
            $t = $albums[$i % 4] . ' #' . ($i + 1);
            Gallery::create(['title' => $t, 'type' => 'image', 'file' => $this->img("galeri-$i", $t, 900, 600, $i), 'album' => $albums[$i % 4],
                'caption' => "[DEMO] Dokumentasi $t", 'sort_order' => $i, 'is_active' => true]);
        }
        foreach ($yt as $i => $v) {
            Gallery::create(['title' => 'Video Profil TSM ' . ($i + 1), 'type' => 'video', 'video_url' => "https://www.youtube.com/watch?v=$v",
                'file' => $this->img("video-$i", 'Video Profil TSM ' . ($i + 1), 900, 600, $i + 1), 'album' => 'Video', 'caption' => '[DEMO] Video YouTube', 'sort_order' => 20 + $i, 'is_active' => true]);
        }

        // ---------- Mitra tambahan ----------
        foreach (['PT Astra Honda Motor', 'PT Yamaha Indonesia', 'Suzuki Indomobil', 'AHASS Blitar Raya', 'Federal Oil', 'Bengkel Mitra Jaya'] as $i => $n) {
            if (!Partner::where('name', $n)->exists()) {
                Partner::create(['name' => $n, 'website' => 'https://example.com', 'category' => $i < 3 ? 'APM' : 'Bengkel/Industri', 'description' => "[DEMO] Mitra industri $n",
                    'logo' => $this->img("mitra-x$i", $n, 400, 200, $i), 'sort_order' => 50 + $i, 'is_active' => true]);
            }
        }

        // ---------- PPDB ----------
        $schools = ['SMPN 1 Blitar', 'SMPN 3 Blitar', 'MTsN Kota Blitar', 'SMP Islam Blitar', 'SMPN 2 Kanigoro', 'MTs Al-Muhsin'];
        $statuses = ['baru', 'baru', 'diproses', 'diterima', 'diterima', 'ditolak'];
        for ($i = 0; $i < 18; $i++) {
            $n = $names[$i % 10] . ($i >= 10 ? ' ' . chr(65 + $i) : '');
            $r = Registration::create(['registration_number' => 'PPDB-2027-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT), 'full_name' => $n, 'nisn' => (string) (3000000000 + $i * 1371),
                'gender' => $i % 3 ? 'L' : 'P', 'birth_date' => Carbon::create(2011, 1 + $i % 12, 1 + $i), 'birth_place' => 'Blitar', 'address' => "Jl. Demo No. $i, Kota Blitar",
                'phone' => '0812' . str_pad((string) (3456000 + $i), 8, '0', STR_PAD_LEFT), 'email' => "calon$i@contoh.id", 'origin_school' => $schools[$i % 6],
                'parent_name' => 'Orang Tua ' . $n, 'parent_phone' => '0857' . str_pad((string) (1200000 + $i), 8, '0', STR_PAD_LEFT), 'notes' => '[DEMO]', 'status' => $statuses[$i % 6]]);
            $r->forceFill(['created_at' => Carbon::now()->subDays($i * 2)])->save();
        }

        // ---------- Pesan kontak ----------
        $msgs = [['Info biaya sekolah', 'Assalamualaikum, berapa biaya pendaftaran dan SPP per bulan?'], ['Kerja sama PKL', 'Kami bengkel resmi di Kediri, ingin menerima siswa PKL.'],
                 ['Servis motor', 'Apakah teaching factory menerima servis motor umum?'], ['Beasiswa', 'Apakah ada beasiswa untuk siswa yatim?'], ['Alumni', 'Saya alumni 2015, ingin ikut reuni.']];
        foreach ($msgs as $i => [$sub, $m]) {
            ContactMessage::create(['name' => $names[$i], 'email' => "pengirim$i@contoh.id", 'phone' => '08123456' . $i . $i, 'subject' => "[DEMO] $sub", 'message' => $m, 'is_read' => $i > 2, 'ip_hash' => md5("m$i")]);
        }

        // ---------- Kunjungan 90 hari ----------
        $postIds = Post::published()->pluck('id')->all();
        $paths = ['/', '/beranda', '/berita', '/ppdb', '/beranda#prestasi', '/beranda#galeri'];
        $devices = ['mobile', 'mobile', 'mobile', 'desktop', 'desktop', 'tablet'];
        $browsers = ['Chrome', 'Chrome', 'Chrome', 'Safari', 'Firefox', 'Edge', 'Samsung Internet'];
        $refs = [null, null, 'https://www.google.com/', 'https://www.facebook.com/', 'https://www.instagram.com/', 'https://wa.me/'];
        $rows = [];
        for ($d = 90; $d >= 0; $d--) {
            $day = Carbon::today()->subDays($d);
            $n = (int) (15 + 25 * (1 - $d / 90) + rand(0, 20) + ($day->isWeekend() ? -8 : 0));
            for ($k = 0; $k < $n; $k++) {
                $pid = rand(0, 2) ? null : ($postIds ? $postIds[array_rand($postIds)] : null);
                $path = $pid ? '/berita/' . Post::find($pid)?->slug : $paths[array_rand($paths)];
                $vh = md5("v$d-" . rand(0, (int) ($n * 0.7)));
                $rows[] = ['url' => 'https://cms-tsm.almuhsinblitar.my.id' . $path, 'path' => $path, 'post_id' => $pid, 'visitor_hash' => $vh, 'ip_hash' => md5('demo-ip'),
                    'referrer' => $refs[array_rand($refs)], 'device' => $devices[array_rand($devices)], 'browser' => $browsers[array_rand($browsers)], 'country' => 'ID',
                    'is_unique' => rand(0, 9) < 7, 'visited_on' => $day->toDateString(), 'created_at' => $day->copy()->addMinutes(rand(360, 1380)), 'updated_at' => now()];
            }
        }
        foreach (array_chunk($rows, 500) as $chunk) Visit::insert($chunk);

        cache()->flush();
        $this->command?->info('Demo selesai: ' . count($posts) . ' berita, ' . count($rows) . ' kunjungan.');
    }

    private function cleanup(): void
    {
        $ids = Post::where('excerpt', 'like', '[DEMO]%')->pluck('id');
        Comment::whereIn('post_id', $ids)->delete();
        PostReaction::whereIn('post_id', $ids)->delete();
        Visit::whereIn('post_id', $ids)->delete();
        foreach (Post::whereIn('id', $ids)->get() as $p) { $p->tags()->detach(); $p->delete(); }
        Visit::where('ip_hash', md5('demo-ip'))->delete();
        Achievement::where('description', 'like', '[DEMO]%')->delete();
        Testimonial::where('photo', 'like', 'dummy/%')->delete();
        Gallery::where('caption', 'like', '[DEMO]%')->delete();
        Teacher::where('bio', 'like', '[DEMO]%')->delete();
        Partner::where('description', 'like', '[DEMO]%')->delete();
        Registration::where('notes', '[DEMO]')->delete();
        ContactMessage::where('subject', 'like', '[DEMO]%')->delete();
        foreach ([Slider::class => 'image', Teacher::class => 'photo', Partner::class => 'logo', Facility::class => 'image', Competency::class => 'image', Program::class => 'image'] as $m => $col) {
            $m::where($col, 'like', 'dummy/%')->update([$col => null]);
        }
    }

    private function body(string $title, string $kw, int $i, string $yt): string
    {
        $kwU = ucfirst($kw);
        return <<<HTML
<p><strong>{$kwU}</strong> menjadi salah satu kegiatan penting di Jurusan Teknik Sepeda Motor (TSM) SMKS Islam 1 Blitar. Kegiatan ini dirancang agar siswa mendapatkan pengalaman nyata sesuai kebutuhan dunia usaha dan dunia industri. Selain itu, kegiatan ini juga menumbuhkan karakter islami, disiplin, dan tanggung jawab.</p>
<h2>Latar Belakang {$kwU}</h2>
<p>Perkembangan teknologi sepeda motor saat ini sangat pesat. Oleh karena itu, sekolah terus memperbarui materi dan peralatan praktik. Namun, peralatan saja tidak cukup; siswa juga perlu dibimbing oleh guru yang kompeten dan berpengalaman di industri. Misalnya, sistem injeksi elektronik kini menggantikan karburator pada hampir semua motor baru.</p>
<p>Menurut Kepala Program Keahlian, <em>"Kami ingin lulusan TSM siap kerja, siap kuliah, dan siap berwirausaha."</em> Pernyataan ini menjadi semangat seluruh warga jurusan.</p>
<blockquote><p>"Ilmu yang bermanfaat adalah ilmu yang diamalkan untuk kebaikan sesama."</p></blockquote>
<h2>Pelaksanaan Kegiatan</h2>
<p>Kegiatan dilaksanakan dalam beberapa tahap. Pertama, siswa mengikuti pembekalan teori di kelas. Kemudian, siswa praktik langsung di bengkel dengan pendampingan guru produktif. Akhirnya, hasil praktik dievaluasi bersama.</p>
<ol><li>Pembekalan materi dan keselamatan kerja (K3).</li><li>Praktik pemeriksaan dan perawatan unit.</li><li>Diagnosis kerusakan menggunakan <u>scanner</u> resmi.</li><li>Evaluasi dan refleksi hasil kerja.</li></ol>
<h3>Peralatan yang Digunakan</h3>
<ul><li>Engine stand dan unit motor praktik</li><li>Multimeter digital dan <s>tester lampu</s> scanner injeksi</li><li>Kunci momen (torque wrench)</li></ul>
<table><tbody><tr><th><p>Tahap</p></th><th><p>Waktu</p></th><th><p>Penanggung Jawab</p></th></tr><tr><td><p>Pembekalan</p></td><td><p>07.00 – 09.00</p></td><td><p>Guru Teori</p></td></tr><tr><td><p>Praktik</p></td><td><p>09.00 – 13.00</p></td><td><p>Guru Produktif</p></td></tr><tr><td><p>Evaluasi</p></td><td><p>13.00 – 14.00</p></td><td><p>Kaprog TSM</p></td></tr></tbody></table>
<h2>Video Dokumentasi</h2>
<p style="text-align: center">Simak dokumentasi kegiatan pada video berikut.</p>
<div data-youtube-video=""><iframe width="640" height="360" allowfullscreen="true" src="https://www.youtube-nocookie.com/embed/{$yt}?controls=1"></iframe></div>
<h2>Hasil dan Harapan</h2>
<p>Dengan demikian, kegiatan {$kw} memberi dampak positif bagi siswa. Bahkan, beberapa siswa mendapat tawaran kerja dari mitra industri. Informasi pendaftaran dapat dilihat di halaman <a href="/ppdb">PPDB</a>, sedangkan info resmi kurikulum ada di <a href="https://kurikulum.kemdikbud.go.id" target="_blank" rel="noopener noreferrer">situs Kemendikbud</a>.</p>
<p><mark>Catatan:</mark> ini adalah konten dummy untuk keperluan pratinjau tampilan.</p>
HTML;
    }
}
