<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Kelas yang sudah ditarik dari aplikasi Data Induk + kolom kunci sinkronisasi siswa.
//
// Kolom `nis` pada akun siswa disimpan sebagai 5 angka pertama NIS (yang tercetak di kartu siswa,
// dipakai untuk login). NIS lengkap sumber disimpan di `nis_lengkap` sebagai keterangan.
// Karena hanya 5 angka yang disimpan, keunikan `nis` tidak bisa dijamin -> unique index dilepas.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('induk_kelas', function (Blueprint $table) {
            $table->id();
            $table->string('induk_id', 50)->unique(); // id rombel di aplikasi Data Induk
            $table->string('nama')->index();
            $table->string('tingkat', 20)->nullable();
            $table->string('jurusan')->nullable();
            $table->unsignedInteger('jumlah_induk')->default(0); // jumlah siswa menurut Data Induk
            $table->unsignedInteger('jumlah_lokal')->default(0); // jumlah siswa di Superapp
            $table->timestamp('ditarik_at')->nullable();
            $table->timestamp('disinkron_at')->nullable();
            $table->string('catatan')->nullable(); // ringkasan hasil sinkron terakhir
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('nis_lengkap', 60)->nullable()->after('nis');
            $table->string('kelas_induk_id', 50)->nullable()->index()->after('kelas');
        });

        // NIS lama (hasil tarik sebelum ini) masih berbentuk angka utuh tanpa tanda baca.
        foreach (DB::table('users')->where('role', 'siswa')->whereNotNull('nis')->get(['id', 'nis']) as $row) {
            $angka = preg_replace('/\D/', '', (string) $row->nis);
            if (strlen($angka) < 5) {
                continue;
            }
            DB::table('users')->where('id', $row->id)->update([
                'nis' => substr($angka, 0, 5),
                'nis_lengkap' => ((string) $row->nis) === $angka ? null : $row->nis,
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nis']);
            $table->index('nis');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['nis']);
            $table->unique('nis');
            $table->dropColumn(['nis_lengkap', 'kelas_induk_id']);
        });

        Schema::dropIfExists('induk_kelas');
    }
};
