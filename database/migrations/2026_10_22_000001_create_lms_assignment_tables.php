<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lms_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('course_id')->constrained('lms_courses')->cascadeOnDelete();
            $t->foreignId('section_id')->nullable()->constrained('lms_sections')->nullOnDelete();
            $t->string('title', 150);
            $t->text('instructions')->nullable();
            $t->dateTime('due_at')->nullable();
            $t->boolean('allow_late')->default(true);
            $t->decimal('max_score', 6, 2)->default(100);
            $t->boolean('is_published')->default(false);
            $t->string('attachment_path', 255)->nullable();
            $t->string('attachment_name', 150)->nullable();
            $t->timestamps();
            $t->index(['course_id', 'is_published']);
        });

        Schema::create('lms_rubric_criteria', function (Blueprint $t) {
            $t->id();
            $t->foreignId('assignment_id')->constrained('lms_assignments')->cascadeOnDelete();
            $t->string('name', 120);
            $t->unsignedSmallInteger('weight')->default(1);
            $t->unsignedTinyInteger('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('lms_submissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('assignment_id')->constrained('lms_assignments')->cascadeOnDelete();
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->string('type', 10)->default('text'); // text | link | file
            $t->text('text')->nullable();
            $t->string('link', 500)->nullable();
            $t->string('file_path', 255)->nullable();
            $t->string('file_name', 150)->nullable();
            $t->string('file_mime', 100)->nullable();
            $t->unsignedBigInteger('file_size')->nullable();
            $t->decimal('score', 6, 2)->nullable();
            $t->text('feedback')->nullable();
            $t->json('rubric_scores')->nullable();
            $t->boolean('is_late')->default(false);
            $t->dateTime('submitted_at');
            $t->dateTime('graded_at')->nullable();
            $t->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['assignment_id', 'student_id']);
            $t->index('student_id');
        });

        Schema::create('lms_submission_files', function (Blueprint $t) {
            $t->id();
            $t->foreignId('submission_id')->constrained('lms_submissions')->cascadeOnDelete();
            $t->string('path', 255);
            $t->string('name', 150);
            $t->string('mime', 100)->nullable();
            $t->unsignedBigInteger('size')->default(0);
            $t->timestamps();
            $t->index('submission_id');
        });
    }

    public function down(): void
    {
        foreach (['lms_submission_files', 'lms_submissions', 'lms_rubric_criteria', 'lms_assignments'] as $tbl) {
            Schema::dropIfExists($tbl);
        }
    }
};
