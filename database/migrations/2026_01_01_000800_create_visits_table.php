<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('url', 500);
            $table->string('path', 255)->nullable();
            $table->foreignId('post_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visitor_hash', 64)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('device', 50)->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->boolean('is_unique')->default(true);
            $table->date('visited_on')->nullable();
            $table->timestamps();
            $table->index(['visited_on']);
            $table->index(['path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
