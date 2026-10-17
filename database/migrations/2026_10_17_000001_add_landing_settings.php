<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menambah pengaturan yang dipakai landing page tapi belum ada barisnya:
 * nomor WhatsApp, dan angka statistik (siswa, alumni, serapan kerja).
 * Idempoten: hanya menambah bila key belum ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('settings')) {
            return;
        }

        $rows = [
            ['key' => 'whatsapp', 'value' => '', 'group' => 'kontak', 'type' => 'text', 'label' => 'Nomor WhatsApp (contoh: 081234567890)'],
            ['key' => 'stat_students', 'value' => '150+', 'group' => 'beranda', 'type' => 'text', 'label' => 'Statistik: siswa aktif'],
            ['key' => 'stat_graduates', 'value' => '500+', 'group' => 'beranda', 'type' => 'text', 'label' => 'Statistik: alumni'],
            ['key' => 'stat_absorption', 'value' => '85%', 'group' => 'beranda', 'type' => 'text', 'label' => 'Statistik: lulusan terserap kerja'],
        ];

        foreach ($rows as $row) {
            if (DB::table('settings')->where('key', $row['key'])->exists()) {
                continue;
            }
            DB::table('settings')->insert($row + [
                'description' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        cache()->forget('site_settings');
    }

    public function down(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('settings')) {
            return;
        }
        DB::table('settings')->whereIn('key', ['whatsapp', 'stat_students', 'stat_graduates', 'stat_absorption'])->delete();
        cache()->forget('site_settings');
    }
};
