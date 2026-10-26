<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lms_questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('course_id')->constrained('lms_courses')->cascadeOnDelete();
            $t->string('type', 20); // pg | benar_salah | pg_kompleks | menjodohkan | isian | uraian
            $t->text('body');
            $t->text('explanation')->nullable();
            $t->decimal('points', 6, 2)->default(1);
            $t->timestamps();
            $t->index('course_id');
        });

        Schema::create('lms_question_options', function (Blueprint $t) {
            $t->id();
            $t->foreignId('question_id')->constrained('lms_questions')->cascadeOnDelete();
            $t->string('body', 500);
            $t->string('match_body', 500)->nullable(); // pasangan kanan (menjodohkan)
            $t->boolean('is_correct')->default(false);
            $t->unsignedSmallInteger('sort_order')->default(0);
        });

        Schema::create('lms_quizzes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('course_id')->constrained('lms_courses')->cascadeOnDelete();
            $t->foreignId('section_id')->nullable()->constrained('lms_sections')->nullOnDelete();
            $t->string('title', 150);
            $t->text('description')->nullable();
            $t->dateTime('opens_at');
            $t->dateTime('closes_at');
            $t->unsignedSmallInteger('duration_min')->nullable();
            $t->boolean('shuffle_questions')->default(true);
            $t->boolean('shuffle_options')->default(true);
            $t->unsignedTinyInteger('max_attempts')->default(1);
            $t->decimal('pass_score', 5, 2)->nullable();
            $t->string('access_code_hash', 100)->nullable();
            $t->string('show_result', 15)->default('after_submit'); // after_submit | after_close | never
            $t->boolean('is_published')->default(false);
            $t->timestamps();
            $t->index('course_id');
        });

        Schema::create('lms_quiz_questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('quiz_id')->constrained('lms_quizzes')->cascadeOnDelete();
            $t->foreignId('question_id')->constrained('lms_questions')->restrictOnDelete();
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->decimal('points', 6, 2)->default(1);
            $t->unique(['quiz_id', 'question_id']);
        });

        Schema::create('lms_quiz_attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('quiz_id')->constrained('lms_quizzes')->cascadeOnDelete();
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->unsignedTinyInteger('attempt_no');
            $t->dateTime('started_at');
            $t->dateTime('deadline_at');
            $t->dateTime('submitted_at')->nullable();
            $t->decimal('score', 6, 2)->nullable();
            $t->string('status', 15)->default('in_progress'); // in_progress | submitted | graded
            $t->unsignedSmallInteger('tab_switch_count')->default(0);
            $t->string('ip', 45)->nullable();
            $t->json('question_order');
            $t->json('option_orders');
            $t->unique(['quiz_id', 'student_id', 'attempt_no']);
            $t->index(['status', 'deadline_at']);
        });

        Schema::create('lms_quiz_answers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('attempt_id')->constrained('lms_quiz_attempts')->cascadeOnDelete();
            $t->foreignId('question_id')->constrained('lms_questions')->cascadeOnDelete();
            $t->json('answer')->nullable();
            $t->boolean('is_correct')->nullable();
            $t->decimal('points_awarded', 6, 2)->nullable();
            $t->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('feedback', 500)->nullable();
            $t->unique(['attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        foreach (['lms_quiz_answers', 'lms_quiz_attempts', 'lms_quiz_questions', 'lms_quizzes', 'lms_question_options', 'lms_questions'] as $x) {
            Schema::dropIfExists($x);
        }
    }
};
