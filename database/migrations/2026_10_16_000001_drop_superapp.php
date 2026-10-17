<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Bersih-bersih TSM Superapp: aplikasi dikembalikan ke CMS saja.
//  - akun superapp (superadmin/guru/siswa/staf) dihapus, hanya admin/editor CMS yang tinggal
//  - kolom siswa/guru (NIS, kelas, NIY, no HP, alamat, dll.) dilepas dari tabel users
//  - tabel feature_user (plotting fitur) dan induk_kelas (kelas hasil tarik Data Induk) dihapus
// Dijaga dengan hasTable/hasColumn supaya tetap aman dijalankan pada database baru.
return new class extends Migration
{
    private const SISWA_GURU = ['nis', 'nis_lengkap', 'nisn', 'birth_date', 'gender', 'kelas', 'kelas_induk_id', 'induk_id', 'synced_at', 'niy', 'phone', 'address'];

    public function up(): void
    {
        DB::table('users')->whereNotIn('role', ['admin', 'editor'])->delete();

        $drop = array_values(array_filter(self::SISWA_GURU, fn ($c) => Schema::hasColumn('users', $c)));
        if ($drop) {
            Schema::table('users', fn (Blueprint $t) => $t->dropColumn($drop));
        }
        if (Schema::hasColumn('users', 'email')) {
            Schema::table('users', fn (Blueprint $t) => $t->string('email')->nullable(false)->change());
        }

        Schema::dropIfExists('feature_user');
        Schema::dropIfExists('induk_kelas');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            if (!Schema::hasColumn('users', 'nis')) $table->string('nis', 20)->nullable()->after('email');
            if (!Schema::hasColumn('users', 'nis_lengkap')) $table->string('nis_lengkap', 60)->nullable()->after('nis');
            if (!Schema::hasColumn('users', 'nisn')) $table->string('nisn', 20)->nullable()->after('nis_lengkap');
            if (!Schema::hasColumn('users', 'birth_date')) $table->date('birth_date')->nullable()->after('nisn');
            if (!Schema::hasColumn('users', 'gender')) $table->string('gender', 1)->nullable()->after('birth_date');
            if (!Schema::hasColumn('users', 'kelas')) $table->string('kelas', 50)->nullable()->index()->after('gender');
            if (!Schema::hasColumn('users', 'kelas_induk_id')) $table->string('kelas_induk_id', 50)->nullable()->index()->after('kelas');
            if (!Schema::hasColumn('users', 'induk_id')) $table->string('induk_id', 50)->nullable()->index()->after('kelas_induk_id');
            if (!Schema::hasColumn('users', 'synced_at')) $table->timestamp('synced_at')->nullable()->after('induk_id');
            if (!Schema::hasColumn('users', 'niy')) $table->string('niy', 30)->nullable()->after('induk_id');
            if (!Schema::hasColumn('users', 'phone')) $table->string('phone', 20)->nullable()->after('niy');
            if (!Schema::hasColumn('users', 'address')) $table->text('address')->nullable()->after('phone');
        });

        if (!Schema::hasTable('induk_kelas')) {
            Schema::create('induk_kelas', function (Blueprint $table) {
                $table->id();
                $table->string('induk_id', 50)->unique();
                $table->string('nama')->index();
                $table->string('tingkat', 20)->nullable();
                $table->string('jurusan')->nullable();
                $table->unsignedInteger('jumlah_induk')->default(0);
                $table->unsignedInteger('jumlah_lokal')->default(0);
                $table->timestamp('ditarik_at')->nullable();
                $table->timestamp('disinkron_at')->nullable();
                $table->string('catatan')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('feature_user')) {
            Schema::create('feature_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('feature', 30);
                $table->string('role', 30);
                $table->timestamps();
                $table->unique(['user_id', 'feature', 'role']);
                $table->index('feature');
            });
        }
    }
};
