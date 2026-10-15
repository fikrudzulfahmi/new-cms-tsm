<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::with(['category', 'user'])->orderByDesc('created_at');
        if ($request->status) $query->where('status', $request->status);
        if ($request->category) $query->where('category_id', $request->category);
        if ($request->q) $query->where('title', 'like', '%' . $request->q . '%');

        return Inertia::render('Admin/Posts/Index', [
            'posts' => $query->paginate(15)->withQueryString(),
            'categories' => Category::active()->get(['id', 'name', 'slug']),
            'filters' => $request->only(['status', 'category', 'q']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Posts/Form', [
            'categories' => Category::active()->get(['id', 'name', 'slug']),
            'tags' => Tag::all(['id', 'name', 'slug']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['title']);
        $data['user_id'] = auth()->id();
        $data['reading_time'] = max(1, round(str_word_count(strip_tags($data['body'] ?? '')) / 200));
        $data['seo_score'] = $this->calcSeoScore($data);
        $data['seo_report'] = json_encode($this->buildSeoReport($data));

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('posts', 'public');
        }
        if ($request->hasFile('og_image')) {
            $data['og_image'] = $request->file('og_image')->store('posts/og', 'public');
        }

        $post = Post::create($data);
        if (!empty($data['tags'])) {
            $tagIds = $this->syncTags($data['tags']);
            $post->tags()->sync($tagIds);
        }

        return redirect()->route('admin.posts.index')->with('success', 'Berita berhasil dipublikasikan!');
    }

    public function edit(Post $post)
    {
        $post->load('tags');
        return Inertia::render('Admin/Posts/Form', [
            'post' => $post,
            'categories' => Category::active()->get(['id', 'name', 'slug']),
            'tags' => Tag::all(['id', 'name', 'slug']),
            'postTags' => $post->tags->pluck('id'),
            'visitStats' => [
                'total' => $post->visits()->count(),
                'last7' => $post->visits()->where('visited_on', '>=', now()->subDays(7))->count(),
            ],
        ]);
    }

    public function update(Request $request, Post $post)
    {
        $data = $request->validate($this->rules($post->id));
        if (empty($data['slug'])) $data['slug'] = Str::slug($data['title']);
        $data['reading_time'] = max(1, round(str_word_count(strip_tags($data['body'] ?? '')) / 200));
        $data['seo_score'] = $this->calcSeoScore($data);
        $data['seo_report'] = json_encode($this->buildSeoReport($data));

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('posts', 'public');
        }
        if ($request->hasFile('og_image')) {
            $data['og_image'] = $request->file('og_image')->store('posts/og', 'public');
        }

        $post->update($data);
        if (isset($data['tags'])) {
            $post->tags()->sync($this->syncTags($data['tags']));
        }
        cache()->forget('site_settings');

        return redirect()->route('admin.posts.index')->with('success', 'Berita berhasil diperbarui!');
    }

    public function destroy(Post $post)
    {
        $post->delete();
        return back()->with('success', 'Berita berhasil dihapus.');
    }

    public function seoAnalyze(Request $request)
    {
        $data = $request->only(['title', 'body', 'meta_title', 'meta_description', 'focus_keyword', 'slug', 'excerpt']);
        return response()->json([
            'score' => $this->calcSeoScore($data),
            'report' => $this->buildSeoReport($data),
        ]);
    }

    public function analytics(Post $post)
    {
        $chart = Visit::selectRaw('DATE(visited_on) as date, COUNT(*) as total')
            ->where('post_id', $post->id)
            ->where('visited_on', '>=', now()->subDays(29))
            ->groupBy('date')->orderBy('date')->get()->keyBy('date');

        $days = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $days[] = ['date' => $d, 'total' => $chart[$d]->total ?? 0];
        }

        return response()->json([
            'post' => $post->only('id', 'title', 'views_count', 'likes_count', 'dislikes_count', 'shares_count', 'comments_count', 'seo_score', 'seo_report'),
            'visitChart' => $days,
        ]);
    }

    private function rules($postId = null): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:posts,slug' . ($postId ? ',' . $postId : ''),
            'category_id' => 'required|exists:categories,id',
            'excerpt' => 'nullable|string|max:500',
            'body' => 'nullable|string',
            'status' => 'required|in:draft,published,scheduled,archived',
            'published_at' => 'nullable|date',
            'is_featured' => 'boolean',
            'allow_comments' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'focus_keyword' => 'nullable|string|max:100',
            'canonical_url' => 'nullable|url',
            'cover_alt' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
        ];
    }

    private function syncTags(array $tags): array
    {
        $ids = [];
        foreach ($tags as $tag) {
            if (is_numeric($tag)) {
                $ids[] = (int)$tag;
            } else {
                $t = Tag::firstOrCreate(['slug' => Str::slug($tag)], ['name' => $tag]);
                $ids[] = $t->id;
            }
        }
        return $ids;
    }

    private function calcSeoScore(array $data): int
    {
        $score = 0;
        $kw = strtolower($data['focus_keyword'] ?? '');
        $title = strtolower($data['title'] ?? '');
        $meta = strtolower($data['meta_description'] ?? '');
        $body = strtolower(strip_tags($data['body'] ?? ''));
        $metaTitle = strtolower($data['meta_title'] ?? '');

        // Title
        if (strlen($data['title'] ?? '') >= 20) $score += 10;
        if (strlen($data['title'] ?? '') <= 60) $score += 5;
        if ($kw && str_contains($title, $kw)) $score += 15;
        // Meta title
        if ($metaTitle && strlen($metaTitle) >= 30 && strlen($metaTitle) <= 60) $score += 10;
        // Meta desc
        if ($meta && strlen($meta) >= 120 && strlen($meta) <= 160) $score += 15;
        if ($kw && str_contains($meta, $kw)) $score += 10;
        // Keyword in body
        if ($kw && str_contains($body, $kw)) $score += 10;
        $density = $kw ? (substr_count($body, $kw) / max(1, str_word_count($body))) * 100 : 0;
        if ($density >= 0.5 && $density <= 2.5) $score += 5;
        // Body length
        if (str_word_count($body) >= 300) $score += 10;
        if (str_word_count($body) >= 600) $score += 5;
        // Slug
        if ($kw && $data['slug'] && str_contains($data['slug'], Str::slug($kw))) $score += 5;

        return min(100, $score);
    }

    private function buildSeoReport(array $data): array
    {
        $kw = strtolower($data['focus_keyword'] ?? '');
        $title = strtolower($data['title'] ?? '');
        $meta = strtolower($data['meta_description'] ?? '');
        $body = strtolower(strip_tags($data['body'] ?? ''));
        $metaTitle = strtolower($data['meta_title'] ?? '');
        $wordCount = str_word_count($body);
        $kwCount = $kw ? substr_count($body, $kw) : 0;
        $density = $wordCount > 0 && $kw ? round(($kwCount / $wordCount) * 100, 2) : 0;
        $titleLen = strlen($data['title'] ?? '');
        $metaLen = strlen($data['meta_description'] ?? '');
        $metaTitleLen = strlen($data['meta_title'] ?? '');

        return [
            'focus_keyword' => [
                'ok' => !empty($kw),
                'msg' => !empty($kw) ? "Kata kunci: \"{$kw}\"" : 'Kata kunci fokus belum diisi.',
            ],
            'title_length' => [
                'ok' => $titleLen >= 20 && $titleLen <= 60,
                'msg' => "Panjang judul: {$titleLen} karakter" . ($titleLen < 20 ? ' (terlalu pendek, min 20)' : ($titleLen > 60 ? ' (terlalu panjang, max 60)' : ' ✓')),
            ],
            'title_keyword' => [
                'ok' => $kw && str_contains($title, $kw),
                'msg' => $kw ? (str_contains($title, $kw) ? 'Kata kunci ada di judul ✓' : 'Kata kunci tidak ada di judul.') : 'Isi kata kunci fokus dahulu.',
            ],
            'meta_title' => [
                'ok' => $metaTitleLen >= 30 && $metaTitleLen <= 60,
                'msg' => "Panjang meta title: {$metaTitleLen} karakter" . ($metaTitleLen == 0 ? ' (kosong)' : ($metaTitleLen < 30 ? ' (terlalu pendek)' : ($metaTitleLen > 60 ? ' (terlalu panjang)' : ' ✓'))),
            ],
            'meta_description' => [
                'ok' => $metaLen >= 120 && $metaLen <= 160,
                'msg' => "Panjang meta deskripsi: {$metaLen} karakter" . ($metaLen == 0 ? ' (kosong)' : ($metaLen < 120 ? ' (terlalu pendek, min 120)' : ($metaLen > 160 ? ' (terlalu panjang, max 160)' : ' ✓'))),
            ],
            'meta_keyword' => [
                'ok' => $kw && str_contains($meta, $kw),
                'msg' => $kw ? (str_contains($meta, $kw) ? 'Kata kunci ada di meta deskripsi ✓' : 'Kata kunci tidak ada di meta deskripsi.') : '-',
            ],
            'word_count' => [
                'ok' => $wordCount >= 300,
                'msg' => "Jumlah kata: {$wordCount}" . ($wordCount < 300 ? ' (min 300 untuk SEO baik)' : ' ✓'),
            ],
            'keyword_density' => [
                'ok' => $density >= 0.5 && $density <= 2.5,
                'msg' => "Densitas kata kunci: {$density}%" . ($density < 0.5 ? ' (terlalu rendah)' : ($density > 2.5 ? ' (terlalu tinggi, hindari spam)' : ' ✓')),
            ],
            'slug_keyword' => [
                'ok' => $kw && $data['slug'] && str_contains($data['slug'] ?? '', Str::slug($kw)),
                'msg' => $kw ? (str_contains($data['slug'] ?? '', Str::slug($kw)) ? 'Kata kunci ada di URL ✓' : 'Kata kunci tidak ada di URL/slug.') : '-',
            ],
        ];
    }
}
