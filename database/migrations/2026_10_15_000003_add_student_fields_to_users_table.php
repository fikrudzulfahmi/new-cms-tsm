<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Data siswa Superapp (ditarik dari aplikasi Data Induk). Siswa login pakai NIS + tanggal lahir,
// jadi email boleh kosong untuk siswa.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('nis', 20)->nullable()->unique()->after('email');
            $table->string('nisn', 20)->nullable()->after('nis');
            $table->date('birth_date')->nullable()->after('nisn');
            $table->string('gender', 1)->nullable()->after('birth_date');
            $table->string('kelas', 50)->nullable()->index()->after('gender');
            $table->string('induk_id', 50)->nullable()->index()->after('kelas');
            $table->timestamp('synced_at')->nullable()->after('induk_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nis']);
            $table->dropIndex(['kelas']);
            $table->dropIndex(['induk_id']);
            $table->dropColumn(['nis', 'nisn', 'birth_date', 'gender', 'kelas', 'induk_id', 'synced_at']);
        });
    }
};
