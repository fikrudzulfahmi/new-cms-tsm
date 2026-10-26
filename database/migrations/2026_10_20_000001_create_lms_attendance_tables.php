<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lms_attendance_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('academic_year_id')->constrained('core_academic_years')->restrictOnDelete();
            $t->string('title', 150);
            $t->text('description')->nullable();
            $t->dateTime('opens_at');            // UTC
            $t->dateTime('closes_at');           // UTC
            $t->dateTime('late_after')->nullable(); // UTC; hadir setelah ini = terlambat
            $t->string('code_hash', 100)->nullable();
            $t->timestamps();
            $t->index(['teacher_id', 'opens_at']);
        });

        Schema::create('lms_attendance_classes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('session_id')->constrained('lms_attendance_sessions')->cascadeOnDelete();
            $t->foreignId('class_id')->constrained('core_classes')->cascadeOnDelete();
            $t->unique(['session_id', 'class_id']);
        });

        Schema::create('lms_attendance_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('session_id')->constrained('lms_attendance_sessions')->cascadeOnDelete();
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->string('status', 10);            // hadir | terlambat | izin | sakit | alpa
            $t->string('note', 255)->nullable();
            $t->dateTime('marked_at');
            $t->string('marked_by', 5)->default('self'); // self | guru
            $t->unique(['session_id', 'student_id']);
        });
    }

    public function down(): void
    {
        foreach (['lms_attendance_records', 'lms_attendance_classes', 'lms_attendance_sessions'] as $x) {
            Schema::dropIfExists($x);
        }
    }
};
