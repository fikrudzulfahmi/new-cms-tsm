<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lms_courses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('academic_year_id')->constrained('core_academic_years')->restrictOnDelete();
            $t->string('title', 150);
            $t->text('description')->nullable();
            $t->boolean('leaderboard_enabled')->default(true);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index(['teacher_id', 'academic_year_id']);
        });

        Schema::create('lms_course_classes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('course_id')->constrained('lms_courses')->cascadeOnDelete();
            $t->foreignId('class_id')->constrained('core_classes')->cascadeOnDelete();
            $t->unique(['course_id', 'class_id']);
        });

        Schema::create('lms_sections', function (Blueprint $t) {
            $t->id();
            $t->foreignId('course_id')->constrained('lms_courses')->cascadeOnDelete();
            $t->string('title', 150);
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('is_published')->default(false);
            $t->timestamps();
        });

        Schema::create('lms_lessons', function (Blueprint $t) {
            $t->id();
            $t->foreignId('section_id')->constrained('lms_sections')->cascadeOnDelete();
            $t->string('type', 10);                 // reading | video | file
            $t->string('title', 150);
            $t->text('content')->nullable();        // teks polos (bukan HTML)
            $t->string('video_url', 255)->nullable(); // URL embed hasil normalisasi (YouTube / Drive)
            $t->string('attachment_path', 255)->nullable();
            $t->string('attachment_name', 150)->nullable();
            $t->unsignedSmallInteger('est_minutes')->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('is_published')->default(false);
            $t->timestamps();
        });

        Schema::create('lms_lesson_progress', function (Blueprint $t) {
            $t->id();
            $t->foreignId('lesson_id')->constrained('lms_lessons')->cascadeOnDelete();
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->timestamp('completed_at')->nullable();
            $t->unique(['lesson_id', 'student_id']);
        });
    }

    public function down(): void
    {
        foreach (['lms_lesson_progress', 'lms_lessons', 'lms_sections', 'lms_course_classes', 'lms_courses'] as $tbl) {
            Schema::dropIfExists($tbl);
        }
    }
};
