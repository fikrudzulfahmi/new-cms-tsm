<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_academic_years', function (Blueprint $t) {
            $t->id();
            $t->string('name', 20)->unique();   // contoh: 2026/2027
            $t->boolean('is_active')->default(false);
            $t->timestamps();
        });

        // Rombel: sumber kebenaran ada di aplikasi Data Induk (kita hanya menyalin).
        Schema::create('core_classes', function (Blueprint $t) {
            $t->id();
            $t->string('external_id', 64)->unique();
            $t->string('name', 100);
            $t->unsignedTinyInteger('level')->nullable();
            $t->string('major', 100)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamp('synced_at')->nullable();
            $t->timestamps();
        });

        Schema::create('core_class_members', function (Blueprint $t) {
            $t->id();
            $t->foreignId('class_id')->constrained('core_classes')->cascadeOnDelete();
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('academic_year_id')->constrained('core_academic_years')->cascadeOnDelete();
            $t->timestamp('left_at')->nullable();
            $t->timestamps();
            $t->unique(['class_id', 'student_id', 'academic_year_id'], 'member_unique');
            $t->index(['student_id', 'academic_year_id', 'left_at']);
        });

        Schema::create('core_sync_runs', function (Blueprint $t) {
            $t->id();
            $t->string('trigger', 20)->default('manual');   // manual | jadwal
            $t->string('status', 20)->default('running');   // running | ok | partial | failed
            $t->foreignId('academic_year_id')->nullable()->constrained('core_academic_years')->nullOnDelete();
            $t->unsignedInteger('classes')->default(0);
            $t->unsignedInteger('created')->default(0);
            $t->unsignedInteger('updated')->default(0);
            $t->unsignedInteger('deactivated')->default(0);
            $t->unsignedInteger('conflicts')->default(0);
            $t->json('details')->nullable();               // daftar bentrok / galat (tanpa data sensitif)
            $t->text('message')->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('finished_at')->nullable();
            $t->timestamps();
        });

        Schema::table('users', function (Blueprint $t) {
            $t->string('nis_full', 40)->nullable()->after('external_id');
            $t->string('nisn', 20)->nullable()->after('nis_full');
            $t->string('gender', 1)->nullable()->after('nisn');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['nis_full', 'nisn', 'gender']));
        Schema::dropIfExists('core_sync_runs');
        Schema::dropIfExists('core_class_members');
        Schema::dropIfExists('core_classes');
        Schema::dropIfExists('core_academic_years');
    }
};
