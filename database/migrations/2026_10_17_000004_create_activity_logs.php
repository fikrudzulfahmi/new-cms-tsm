<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log aktivitas admin: dipakai untuk audit perubahan data sekaligus
 * mendeteksi percobaan akses tidak sah (login gagal, 403, 429).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activity_logs')) {
            return;
        }

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name', 150)->nullable();
            $table->string('role', 20)->nullable();
            $table->string('action', 30);
            $table->string('module', 40)->nullable();
            $table->text('description')->nullable();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('method', 10)->nullable();
            $table->string('path', 255)->nullable();
            $table->unsignedSmallInteger('status')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['action', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['ip', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
