<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Pembuat slug yang dijamin unik.
 *
 * Dipakai semua modul yang punya kolom `slug` unik (posts, categories,
 * competencies). Tanpa ini, dua entri dengan nama/judul yang sama membuat
 * insert gagal dengan QueryException (halaman 500).
 */
class Slug
{
    public static function unique(string $table, ?string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug((string) $value) ?: Str::random(8);
        $slug = $base;
        $i = 2;

        while (DB::table($table)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
