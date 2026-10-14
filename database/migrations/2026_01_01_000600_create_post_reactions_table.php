<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('visitor_hash', 64);
            $table->tinyInteger('value'); // 1 = like, -1 = dislike
            $table->timestamps();
            $table->unique(['post_id', 'visitor_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_reactions');
    }
};
