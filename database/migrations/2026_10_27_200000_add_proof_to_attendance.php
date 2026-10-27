<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lms_attendance_sessions', function (Blueprint $t) {
            $t->boolean('require_proof')->default(false)->after('code_hash'); // selfie + lokasi wajib
        });
        Schema::table('lms_attendance_records', function (Blueprint $t) {
            $t->string('photo_path', 160)->nullable();
            $t->decimal('lat', 10, 7)->nullable();
            $t->decimal('lng', 10, 7)->nullable();
            $t->unsignedInteger('accuracy')->nullable(); // meter
        });
    }

    public function down(): void
    {
        Schema::table('lms_attendance_records', fn (Blueprint $t) => $t->dropColumn(['photo_path', 'lat', 'lng', 'accuracy']));
        Schema::table('lms_attendance_sessions', fn (Blueprint $t) => $t->dropColumn('require_proof'));
    }
};
