<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pengaturan untuk halaman /profil: sambutan, sejarah, visi, misi, dan
 * struktur organisasi. Idempoten: hanya menambah key yang belum ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('settings')) {
            return;
        }

        $rows = [
            ['profile_head_name', '', 'text', 'Nama Kepala Program/Jurusan (kosong = guru urutan pertama)'],
            ['profile_head_position', 'Kepala Program Keahlian TSM', 'text', 'Jabatan penyambut'],
            ['profile_head_photo', '', 'image', 'Foto penyambut'],
            ['profile_speech', "Assalamu'alaikum warahmatullahi wabarakatuh.\n\nKami berkomitmen membekali setiap siswa dengan keterampilan nyata, karakter Islami, dan kesiapan kerja agar mampu bersaing di dunia industri otomotif.", 'textarea', 'Sambutan'],
            ['profile_history', '', 'textarea', 'Sejarah jurusan'],
            ['profile_vision', 'Menjadi program keahlian Teknik Sepeda Motor yang unggul, berkarakter Islami, dan menghasilkan lulusan siap kerja serta berwirausaha.', 'textarea', 'Visi'],
            ['profile_mission', "Menyelenggarakan pembelajaran berbasis praktik sesuai standar industri.\nMenanamkan akhlak Islami dan budaya kerja disiplin.\nMenjalin kemitraan dengan dunia usaha dan dunia industri.\nMengembangkan jiwa kewirausahaan siswa di bidang otomotif.", 'textarea', 'Misi (satu poin per baris)'],
            ['profile_structure_image', '', 'image', 'Bagan struktur organisasi'],
        ];

        foreach ($rows as [$key, $value, $type, $label]) {
            if (DB::table('settings')->where('key', $key)->exists()) {
                continue;
            }
            DB::table('settings')->insert([
                'key' => $key, 'value' => $value, 'group' => 'profil', 'type' => $type,
                'label' => $label, 'description' => null, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        cache()->forget('site_settings');
        cache()->forget('public_settings');
    }

    public function down(): void
    {
        DB::table('settings')->where('group', 'profil')->where('key', 'like', 'profile_%')->delete();
    }
};
