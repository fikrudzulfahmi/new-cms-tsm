<?php

namespace App\Http\Controllers;

use App\Models\Competency;
use App\Models\Post;
use Illuminate\Http\Response;

/**
 * Sitemap XML sederhana: halaman depan, daftar berita, dan setiap artikel
 * yang sudah terbit. Dipakai Google Search Console setelah situs live.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [
            ['loc' => url('/'), 'lastmod' => now()->toAtomString(), 'priority' => '1.0'],
            ['loc' => url('/berita'), 'lastmod' => now()->toAtomString(), 'priority' => '0.8'],
        ];
        foreach (['profil', 'guru', 'kompetensi', 'program', 'sarana', 'prestasi', 'alumni'] as $page) {
            $urls[] = ['loc' => url('/'.$page), 'lastmod' => now()->toAtomString(), 'priority' => '0.7'];
        }
        Competency::active()->get(['slug', 'updated_at'])->each(function ($c) use (&$urls) {
            if ($c->slug) {
                $urls[] = ['loc' => url('/kompetensi/'.$c->slug), 'lastmod' => optional($c->updated_at)->toAtomString(), 'priority' => '0.6'];
            }
        });

        Post::published()
            ->orderByDesc('updated_at')
            ->select(['slug', 'updated_at'])
            ->chunk(500, function ($posts) use (&$urls) {
                foreach ($posts as $post) {
                    $urls[] = [
                        'loc' => url('/berita/'.$post->slug),
                        'lastmod' => optional($post->updated_at)->toAtomString(),
                        'priority' => '0.6',
                    ];
                }
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n    <loc>".htmlspecialchars($u['loc'], ENT_XML1)."</loc>\n";
            if (! empty($u['lastmod'])) {
                $xml .= '    <lastmod>'.$u['lastmod']."</lastmod>\n";
            }
            $xml .= '    <priority>'.$u['priority']."</priority>\n  </url>\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
