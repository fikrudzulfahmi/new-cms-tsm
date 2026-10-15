<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

// Catatan kelas yang sudah ditarik dari aplikasi Data Induk (penanda "jangan tarik ulang, pakai Sinkron").
#[Fillable(['induk_id', 'nama', 'tingkat', 'jurusan', 'jumlah_induk', 'jumlah_lokal', 'ditarik_at', 'disinkron_at', 'catatan'])]
class IndukKelas extends Model
{
    protected $table = 'induk_kelas';

    protected function casts(): array
    {
        return [
            'ditarik_at' => 'datetime',
            'disinkron_at' => 'datetime',
            'jumlah_induk' => 'integer',
            'jumlah_lokal' => 'integer',
        ];
    }
}
