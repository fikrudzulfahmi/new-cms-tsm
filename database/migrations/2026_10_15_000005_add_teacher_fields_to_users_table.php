<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Data guru (input manual oleh superadmin): NIY, no HP (bisa dipakai login), alamat.
// Jenis kelamin memakai kolom `gender` yang sudah ada.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('niy', 30)->nullable()->unique()->after('induk_id');
            $table->string('phone', 20)->nullable()->unique()->after('niy'); // disimpan bentuk 08xxxxxxxxxx
            $table->text('address')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['niy']);
            $table->dropUnique(['phone']);
            $table->dropColumn(['niy', 'phone', 'address']);
        });
    }
};
