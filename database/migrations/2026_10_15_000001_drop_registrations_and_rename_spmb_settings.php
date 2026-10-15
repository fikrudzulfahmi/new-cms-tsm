<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Pendaftaran SPMB memakai aplikasi sekolah sendiri: tabel formulir lama dihapus,
// setting ppdb_* diganti spmb_* (spmb_open, spmb_info, spmb_url).
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('registrations');

        $rows = [
            'ppdb_open' => ['spmb_open', 'boolean', 'SPMB Dibuka', '1'],
            'ppdb_info' => ['spmb_info', 'textarea', 'Info SPMB', ''],
        ];
        foreach ($rows as $old => [$new, $type, $label, $default]) {
            $value = DB::table('settings')->where('key', $old)->value('value') ?? $default;
            DB::table('settings')->where('key', $old)->delete();
            DB::table('settings')->updateOrInsert(['key' => $new], ['value' => $value, 'group' => 'spmb', 'type' => $type, 'label' => $label, 'updated_at' => now()]);
        }
        DB::table('settings')->updateOrInsert(['key' => 'spmb_url'], [
            'value' => DB::table('settings')->where('key', 'spmb_url')->value('value') ?? '',
            'group' => 'spmb', 'type' => 'text', 'label' => 'Link Aplikasi SPMB', 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        foreach (['spmb_open' => 'ppdb_open', 'spmb_info' => 'ppdb_info'] as $new => $old) {
            DB::table('settings')->where('key', $new)->update(['key' => $old, 'group' => 'ppdb']);
        }
    }
};
