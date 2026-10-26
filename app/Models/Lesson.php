<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $table = 'lms_lessons';

    protected $fillable = [
        'section_id', 'type', 'title', 'content', 'video_url', 'attachment_path',
        'attachment_name', 'est_minutes', 'sort_order', 'is_published',
    ];

    protected $casts = ['is_published' => 'boolean'];

    protected $hidden = ['attachment_path'];

    public function section()
    {
        return $this->belongsTo(CourseSection::class, 'section_id');
    }

    /** Hanya YouTube / Google Drive yang boleh disematkan. Hasilnya URL embed, atau null. */
    public static function normalizeVideoUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if (! preg_match('#^https://#i', $url)) {
            return null;
        }
        $p = parse_url($url);
        $host = strtolower($p['host'] ?? '');
        $host = preg_replace('/^(www\.|m\.)/', '', $host);
        $path = $p['path'] ?? '';
        parse_str($p['query'] ?? '', $q);

        $yt = null;
        if ($host === 'youtu.be') {
            $yt = trim($path, '/');
        } elseif (in_array($host, ['youtube.com', 'youtube-nocookie.com'], true)) {
            if ($path === '/watch') {
                $yt = $q['v'] ?? null;
            } elseif (preg_match('#^/(embed|shorts|live)/([^/]+)#', $path, $m)) {
                $yt = $m[2];
            }
        }
        if ($yt !== null && preg_match('/^[A-Za-z0-9_-]{11}$/', $yt)) {
            return 'https://www.youtube-nocookie.com/embed/'.$yt;
        }

        if ($host === 'drive.google.com' && preg_match('#^/file/d/([A-Za-z0-9_-]{10,})#', $path, $m)) {
            return 'https://drive.google.com/file/d/'.$m[1].'/preview';
        }

        return null;
    }
}
