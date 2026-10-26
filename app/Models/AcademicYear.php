<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $table = 'core_academic_years';

    protected $fillable = ['name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public static function active(): ?self
    {
        return static::where('is_active', true)->first();
    }

    /** Hanya satu tahun ajaran aktif pada satu waktu. */
    public function activate(): void
    {
        \DB::transaction(function () {
            static::where('id', '!=', $this->id)->update(['is_active' => false]);
            $this->forceFill(['is_active' => true])->save();
        });
    }
}
