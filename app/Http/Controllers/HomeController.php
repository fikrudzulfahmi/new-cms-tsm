<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Category;
use App\Models\Competency;
use App\Models\Facility;
use App\Models\Gallery;
use App\Models\Partner;
use App\Models\Post;
use App\Models\Program;
use App\Models\Setting;
use App\Models\Slider;
use App\Models\Teacher;
use App\Models\Testimonial;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function landing(Request $request)
    {
        $this->trackVisit($request, null);

        $categories = Category::active()
            ->withCount(['posts' => fn($q) => $q->published()])
            ->orderBy('sort_order')->get();

        // Berita terbaru + 4 terbaru per kategori, agar tab kategori selalu terisi.
        $postCols = ['id', 'category_id', 'title', 'slug', 'excerpt', 'cover_image', 'cover_alt', 'published_at', 'reading_time', 'views_count', 'is_featured'];
        // Satu query berjendela (ROW_NUMBER) untuk 4 berita terbaru per kategori,
        // menggantikan N+1 query di dalam loop kategori.
        $ranked = DB::table('posts')
            ->select('id')
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY category_id ORDER BY published_at DESC, id DESC) as rn')
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereIn('category_id', $categories->pluck('id')->all());
        $ids = DB::query()->fromSub($ranked, 'ranked')->where('rn', '<=', 4)->pluck('id')->all();
        $posts = Post::with('category:id,name,slug,color')
            ->whereIn('id', $ids)
            ->orderByDesc('published_at')
            ->get($postCols);

        return Inertia::render('Landing/Index', [
            'sliders' => Slider::active()->take(8)->get(),
            'competencies' => Competency::active()->take(6)->get(),
            'programs' => Program::active()->take(4)->get(),
            'facilities' => Facility::active()->take(6)->get(),
            'achievements' => Achievement::active()->orderByDesc('is_featured')->orderByDesc('achieved_at')->take(6)->get(),
            'posts' => $posts,
            'categories' => $categories,
            'testimonials' => Testimonial::approved()->orderByDesc('is_featured')->latest()->take(3)->get(),
            'teachers' => Teacher::active()->take(4)->get(['id', 'name', 'position', 'subject', 'photo']),
            'partners' => Partner::active()->take(12)->get(),
            'gallery' => Gallery::active()->images()->take(8)->get(),
            'stats' => [
                'students' => (Setting::get('stat_students') ?: '150+'),
                'graduates' => (Setting::get('stat_graduates') ?: '500+'),
                'absorption' => (Setting::get('stat_absorption') ?: '85%'),
                'achievements' => Achievement::active()->count(),
                'partners' => Partner::active()->count(),
                'teachers' => Teacher::active()->count(),
            ],
        ]);
    }

    // ===================== HALAMAN PER BAGIAN =====================

    public function profile(Request $request)
    {
        $this->trackVisit($request, null);
        $mission = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) Setting::get('profile_mission')))));

        return Inertia::render('Profile/Index', [
            'head' => Teacher::active()->first(['id', 'name', 'position', 'photo']),
            'mission' => $mission,
            'teachers' => Teacher::active()->take(8)->get(['id', 'name', 'position', 'subject', 'photo']),
            'teacherCount' => Teacher::active()->count(),
        ]);
    }

    public function teachers(Request $request)
    {
        $this->trackVisit($request, null);
        return Inertia::render('Teacher/Index', [
            'teachers' => Teacher::active()->get(['id', 'name', 'position', 'subject', 'education', 'photo']),
        ]);
    }

    public function competencies(Request $request)
    {
        $this->trackVisit($request, null);
        return Inertia::render('Competency/Index', [
            'competencies' => Competency::active()->get(['id', 'title', 'slug', 'icon', 'short_description', 'image']),
        ]);
    }

    public function competency(Request $request, string $slug)
    {
        $competency = Competency::active()->where('slug', $slug)->firstOrFail();
        $this->trackVisit($request, null);
        return Inertia::render('Competency/Show', [
            'competency' => $competency,
            'others' => Competency::active()->whereKeyNot($competency->id)->get(['id', 'title', 'slug', 'icon', 'short_description']),
        ]);
    }

    public function programs(Request $request)
    {
        $this->trackVisit($request, null);
        return Inertia::render('Program/Index', [
            'programs' => Program::active()->get(['id', 'title', 'subtitle', 'icon', 'description', 'image']),
        ]);
    }

    public function facilities(Request $request)
    {
        $this->trackVisit($request, null);
        return Inertia::render('Facility/Index', [
            'facilities' => Facility::active()->get(['id', 'name', 'category', 'description', 'image', 'quantity', 'condition']),
        ]);
    }

    public function achievements(Request $request)
    {
        $this->trackVisit($request, null);
        $level = mb_substr((string) $request->get('tingkat'), 0, 50);
        // Prestasi tanpa tanggal (hanya tahun) jangan terlempar ke bawah: nulls last.
        $query = Achievement::active()->reorder()->orderByDesc('is_featured')
            ->orderByRaw('achieved_at IS NULL')->orderByDesc('achieved_at')->orderByDesc('year')->orderByDesc('id');
        if ($level !== '') {
            $query->where('level', $level);
        }
        return Inertia::render('Achievement/Index', [
            'achievements' => $query->paginate(12, ['id', 'title', 'student_name', 'level', 'rank', 'organizer', 'year', 'achieved_at', 'description', 'image', 'is_featured'])->withQueryString(),
            'levels' => Achievement::active()->reorder()->whereNotNull('level')->where('level', '!=', '')->distinct()->orderBy('level')->pluck('level'),
            'filters' => ['tingkat' => $level],
        ]);
    }

    public function alumni(Request $request)
    {
        $this->trackVisit($request, null);
        return Inertia::render('Alumni/Index', [
            'testimonials' => Testimonial::approved()->orderByDesc('is_featured')->latest()
                ->paginate(12, ['id', 'name', 'graduation_year', 'occupation', 'workplace', 'message', 'rating', 'photo', 'is_featured']),
        ]);
    }

    public function posts(Request $request)
    {
        $this->trackVisit($request, null);
        $category = $request->get('kategori') ?: $request->get('category');
        if ($request->get('search') && !$request->get('q')) $request->merge(['q' => $request->get('search')]);
        $query = Post::published()->with(['category', 'user:id,name,avatar'])->orderByDesc('published_at');
        if ($category) {
            $query->whereHas('category', fn($q) => $q->where('slug', $category));
        }
        if ($request->get('q')) {
            $term = '%' . addcslashes(mb_substr((string) $request->q, 0, 100), '%_\\') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                  ->orWhere('excerpt', 'like', $term);
            });
        }
        return Inertia::render('Post/Index', [
            'filters' => ['search' => $request->get('q'), 'category' => $category],
            'posts' => $query->paginate(9)->withQueryString(),
            'categories' => Category::active()->withCount(['posts' => fn($q) => $q->published()])->get(),
            'currentCategory' => $category,
            'query' => $request->get('q'),
        ]);
    }

    public function post(Request $request, $slug)
    {
        $post = Post::published()->where('slug', $slug)->with(['category', 'user:id,name,avatar', 'tags'])->firstOrFail();
        $this->trackVisit($request, $post->id);
        if (! $this->isBot($request)) {
            $post->increment('views_count');
        }

        $visitorHash = $this->getVisitorHash($request);
        $reaction = $post->reactions()->where('visitor_hash', $visitorHash)->first();

        $related = Post::published()
            ->where('id', '!=', $post->id)
            ->where('category_id', $post->category_id)
            ->with('category')
            ->orderByDesc('published_at')
            ->take(3)->get();

        $comments = $post->comments()
            ->approved()
            ->with('replies')
            ->whereNull('parent_id')
            ->orderByDesc('created_at')
            ->get();
        $post->setRelation('comments', $comments);
        $post->setAttribute('rating', $post->ratioLikeDislike());

        return Inertia::render('Post/Show', [
            'relatedPosts' => $related,
            'post' => $post,
            'related' => $related,
            'comments' => $comments,
            'myReaction' => $reaction ? $reaction->value : 0,
        ]);
    }

    public function react(Request $request, $postId)
    {
        $validated = $request->validate(['value' => 'required|in:1,-1']);
        $post = Post::published()->findOrFail($postId);
        $hash = $this->getVisitorHash($request);
        $value = (int) $validated['value']; // 1 or -1

        // lockForUpdate mencegah dua request paralel sama-sama menambah counter
        // (race condition yang membuat likes_count tidak sinkron).
        $myReaction = DB::transaction(function () use ($post, $hash, $value) {
            $post = Post::whereKey($post->getKey())->lockForUpdate()->firstOrFail();
            $existing = $post->reactions()->where('visitor_hash', $hash)->first();

            if ($existing) {
                if ((int) $existing->value === $value) {
                    $post->reactions()->whereKey($existing->getKey())->delete();
                    $this->safeDecrement($post, $value === 1 ? 'likes_count' : 'dislikes_count');
                    return 0;
                }

                $existing->update(['value' => $value]);
                if ($value === 1) {
                    $post->increment('likes_count');
                    $this->safeDecrement($post, 'dislikes_count');
                } else {
                    $post->increment('dislikes_count');
                    $this->safeDecrement($post, 'likes_count');
                }

                return $value;
            }

            $post->reactions()->create(['visitor_hash' => $hash, 'value' => $value]);
            $post->increment($value === 1 ? 'likes_count' : 'dislikes_count');

            return $value;
        });

        $post->refresh();
        return response()->json([
            'likes' => $post->likes_count,
            'dislikes' => $post->dislikes_count,
            'my_reaction' => $myReaction,
            'ratio' => $post->ratioLikeDislike(),
        ]);
    }

    public function comment(Request $request, $postId)
    {
        if ($this->isSpam($request)) {
            return back()->with('success', 'Komentar terkirim, menunggu moderasi. Terima kasih!');
        }

        // Bersihkan tag SEBELUM validasi supaya isian yang hanya berisi tag
        // tidak lolos "required" lalu tersimpan kosong.
        $request->merge([
            'name' => trim(strip_tags((string) $request->input('name'))),
            'body' => trim(strip_tags((string) $request->input('body'))),
        ]);

        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'nullable|email|max:150',
            'body' => 'required|string|max:2000',
            'parent_id' => 'nullable|integer',
        ]);

        $post = Post::published()->findOrFail($postId);
        if (!$post->allow_comments) {
            return back()->with('error', 'Komentar dinonaktifkan untuk berita ini.');
        }

        if ($request->parent_id) {
            // Hanya boleh membalas komentar utama yang sudah disetujui; ID komentar
            // pending/spam tidak boleh bisa ditebak lewat balasan.
            $parent = $post->comments()->approved()->whereNull('parent_id')->whereKey($request->parent_id)->first();
            if (! $parent) {
                return back()->withErrors(['body' => 'Komentar yang dibalas tidak ditemukan.']);
            }
        }

        $comment = $post->comments()->create([
            'parent_id' => $request->parent_id,
            'name' => $request->name,
            'email' => $request->email,
            'body' => $request->body,
            'status' => 'pending',
            'ip_hash' => hash('sha256', $request->ip()),
            'user_agent' => substr($request->userAgent() ?? '', 0, 255),
        ]);

        return back()->with('success', 'Komentar terkirim, menunggu moderasi. Terima kasih!');
    }

    public function likeComment(Request $request, $commentId)
    {
        $comment = \App\Models\Comment::approved()->findOrFail($commentId);
        $hash = $this->getVisitorHash($request);
        DB::transaction(function () use ($comment, $hash) {
            $comment = \App\Models\Comment::whereKey($comment->getKey())->lockForUpdate()->firstOrFail();
            $existing = $comment->reactions()->where('visitor_hash', $hash)->first();
            if ($existing) {
                $existing->delete();
                $this->safeDecrement($comment, 'likes_count');
            } else {
                $comment->reactions()->create(['visitor_hash' => $hash]);
                $comment->increment('likes_count');
            }
        });
        return response()->json(['likes' => $comment->fresh()->likes_count]);
    }

    public function share(Request $request, $postId)
    {
        $post = Post::published()->findOrFail($postId);
        // Satu pengunjung hanya menambah angka bagikan sekali per hari.
        $key = 'share:'.$post->id.':'.$this->getVisitorHash($request).':'.now('Asia/Jakarta')->toDateString();
        if (cache()->add($key, 1, now()->addDay())) {
            $post->increment('shares_count');
        }
        return response()->json(['ok' => true]);
    }

    public function spmb()
    {
        $url = trim((string) Setting::get('spmb_url'));
        if ($url && preg_match('#^https?://#i', $url)) {
            return redirect()->away($url);
        }
        return redirect('/#spmb');
    }

    public function contact(Request $request)
    {
        if ($this->isSpam($request)) {
            return back()->with('success', 'Pesan berhasil terkirim! Kami akan segera menghubungi Anda.');
        }

        foreach (['name', 'subject', 'message', 'phone'] as $field) {
            $request->merge([$field => trim(strip_tags((string) $request->input($field)))]);
        }

        $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required_without:phone|nullable|email|max:150',
            'phone' => 'required_without:email|nullable|string|max:20',
            'subject' => 'nullable|string|max:200',
            'message' => 'required|string|max:2000',
        ], [
            'email.required_without' => 'Isi email atau nomor HP agar kami bisa membalas.',
            'phone.required_without' => 'Isi email atau nomor HP agar kami bisa membalas.',
        ]);
        \App\Models\ContactMessage::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'subject' => $request->subject,
            'message' => $request->message,
            'ip_hash' => hash('sha256', $request->ip()),
        ]);
        return back()->with('success', 'Pesan berhasil terkirim! Kami akan segera menghubungi Anda.');
    }

    public function testimonialStore(Request $request)
    {
        if ($this->isSpam($request)) {
            return back()->with('success', 'Terima kasih! Testimoni Anda sudah terkirim dan akan tampil setelah disetujui admin.');
        }

        foreach (['name', 'occupation', 'workplace', 'message'] as $field) {
            $request->merge([$field => trim(strip_tags((string) $request->input($field)))]);
        }

        $request->validate([
            'name' => 'required|string|max:150',
            'graduation_year' => 'required|integer|min:2000|max:' . (date('Y')),
            'occupation' => 'nullable|string|max:150',
            'workplace' => 'nullable|string|max:150',
            'message' => 'required|string|max:1000',
            'rating' => 'required|integer|min:1|max:5',
        ]);
        Testimonial::create([
            'name' => $request->name,
            'graduation_year' => $request->graduation_year,
            'occupation' => $request->occupation,
            'workplace' => $request->workplace,
            'message' => $request->message,
            'rating' => $request->rating,
            'status' => 'pending',
        ]);
        return back()->with('success', 'Terima kasih! Testimoni Anda sudah terkirim dan akan tampil setelah disetujui admin.');
    }

    private function trackVisit(Request $request, $postId)
    {
        // Jangan catat bot/mesin pencari/preview link agar statistik tidak menggelembung.
        if ($this->isBot($request)) {
            return;
        }

        $hash = $this->getVisitorHash($request);
        // Tanggal laporan pakai WIB, bukan UTC, supaya kunjungan malam tidak masuk hari sebelumnya.
        $today = now('Asia/Jakarta')->toDateString();
        $path = $request->path();

        $unique = !Visit::where('visitor_hash', $hash)->whereDate('visited_on', $today)->exists();

        Visit::create([
            'url' => mb_substr($request->url(), 0, 500),
            'path' => mb_substr('/' . $path, 0, 255),
            'post_id' => $postId,
            'visitor_hash' => $hash,
            'ip_hash' => hash('sha256', $request->ip()),
            'referrer' => substr($request->header('referer', '') ?? '', 0, 499),
            'device' => $this->detectDevice($request->userAgent() ?? ''),
            'browser' => $this->detectBrowser($request->userAgent() ?? ''),
            'is_unique' => $unique,
            'visited_on' => $today,
        ]);
    }

    /** Honeypot: field tersembunyi yang hanya diisi bot. */
    private function isSpam(Request $request): bool
    {
        return filled($request->input('website')) || filled($request->input('hp_field'));
    }

    /** Kurangi counter tanpa pernah menghasilkan angka negatif (kolom unsigned). */
    private function safeDecrement($model, string $column): void
    {
        $model->newQuery()->whereKey($model->getKey())->update([
            $column => DB::raw('GREATEST('.$column.', 1) - 1'),
        ]);
        $model->setAttribute($column, max(0, (int) $model->getAttribute($column) - 1));
    }

    private function isBot(Request $request): bool
    {
        $ua = (string) $request->userAgent();

        return $ua === '' || (bool) preg_match('/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|whatsapp|telegram|preview|curl|wget|headless|monitor/i', $ua);
    }

    private function getVisitorHash(Request $request): string
    {
        return hash('sha256', $request->ip() . $request->userAgent() . date('Y-m-d'));
    }

    private function detectDevice(string $ua): string
    {
        if (preg_match('/iPad|Tablet/i', $ua)) return 'tablet';
        if (preg_match('/Mobile|Android|iPhone/i', $ua)) return 'mobile';
        return 'desktop';
    }

    private function detectBrowser(string $ua): string
    {
        if (str_contains($ua, 'Chrome')) return 'Chrome';
        if (str_contains($ua, 'Firefox')) return 'Firefox';
        if (str_contains($ua, 'Safari')) return 'Safari';
        if (str_contains($ua, 'Edge')) return 'Edge';
        return 'Other';
    }
}
