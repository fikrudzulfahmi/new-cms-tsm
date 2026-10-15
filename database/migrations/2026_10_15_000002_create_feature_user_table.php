<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Superapp: plotting user ke fitur (lms/pkl/ukk/perpus) beserta perannya di fitur tsb.
// users.role: admin/editor = CMS, superadmin = Superapp, guru/siswa = pengguna Superapp.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('feature', 30);
            $table->string('role', 30);
            $table->timestamps();
            $table->unique(['user_id', 'feature', 'role']);
            $table->index('feature');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_user');
    }
};
