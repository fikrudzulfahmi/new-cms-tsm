<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Index tambahan supaya laporan kunjungan & daftar berita publik tetap cepat. */
    public function up(): void
    {
        $this->addIndexIfMissing('visits', ['browser'], 'visits_browser_index');
        $this->addIndexIfMissing('visits', ['device'], 'visits_device_index');
        $this->addIndexIfMissing('visits', ['post_id', 'visited_on'], 'visits_post_id_visited_on_index');
        $this->addIndexIfMissing('posts', ['status', 'published_at'], 'posts_status_published_at_index');
    }

    public function down(): void
    {
        foreach ([
            ['visits', 'visits_browser_index'],
            ['visits', 'visits_device_index'],
            ['visits', 'visits_post_id_visited_on_index'],
            ['posts', 'posts_status_published_at_index'],
        ] as [$table, $index]) {
            if ($this->hasIndex($table, $index)) {
                Schema::table($table, fn (Blueprint $t) => $t->dropIndex($index));
            }
        }
    }

    private function addIndexIfMissing(string $table, array $columns, string $name): void
    {
        if (! Schema::hasTable($table) || $this->hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
    }

    private function hasIndex(string $table, string $name): bool
    {
        $indexes = DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$name]);

        return ! empty($indexes);
    }
};
