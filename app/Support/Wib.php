<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class Wib
{
    public static function parse(?string $v): ?Carbon
    {
        return $v ? Carbon::createFromFormat('Y-m-d\TH:i', $v, 'Asia/Jakarta')->utc() : null;
    }

    public static function format($dt): ?string
    {
        return $dt ? $dt->copy()->setTimezone('Asia/Jakarta')->format('Y-m-d\TH:i') : null;
    }
}
