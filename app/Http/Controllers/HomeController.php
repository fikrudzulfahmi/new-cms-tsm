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
        $posts = Post::published()->with('category:id,name,slug,color')
            ->orderByDesc('published_at')->take(8)->get($postCols);
        foreach ($categories as $cat) {
            $posts = $posts->merge(
                Post::published()->with('category:id,name,slug,color')
                    ->where('category_id', $cat->id)
                    ->orderByDesc('published_at')->take(4)->get($postCols)
            );
        }
        $posts = $posts->unique('id')->sortByDesc('published_at')->values();

        return Inertia::render('Landing/Index', [
            'sliders' => Slider::active()->get(),
            'competencies' => Competency::active()->get(),
            'programs' => Program::active()->get(),
            'facilities' => Facility::active()->take(9)->get(),
            'achievements' => Achievement::active()->orderByDesc('is_featured')->orderByDesc('achieved_at')->take(6)->get(),
            'posts' => $posts,
            'categories' => $categories,
            'testimonials' => Testimonial::approved()->orderByDesc('is_featured')->latest()->take(6)->get(),
            'teachers' => Teacher::active()->take(8)->get(),
            'partners' => Partner::active()->get(),
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

    public function posts(Request $request)
    {
        $this->trackVisit($request, null);
        $category = $request->get('kategori') ?: $request->get('category');
        if ($request->get('search') && !$request->get('q')) $request->merge(['q' => $request->get('search')]);
        $query = Post::published()->with(['category', 'user'])->orderByDesc('published_at');
        if ($category) {
            $query->whereHas('category', fn($q) => $q->where('slug', $category));
        }
        if ($request->get('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->q . '%')
                  ->orWhere('excerpt', 'like', '%' . $request->q . '%');
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
        $post = Post::published()->where('slug', $slug)->with(['category', 'user', 'tags'])->firstOrFail();
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
            ->with('replies.reactions')
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

        $existing = $post->reactions()->where('visitor_hash', $hash)->first();

        if ($existing) {
            if ($existing->value === $value) {
                // Toggle off
                if ($value == 1) $post->decrement('likes_count');
                else $post->decrement('dislikes_count');
                $existing->delete();
                $myReaction = 0;
            } else {
                // Switch
                if ($value == 1) { $post->increment('likes_count'); $post->decrement('dislikes_count'); }
                else { $post->decrement('likes_count'); $post->increment('dislikes_count'); }
                $existing->update(['value' => $value]);
                $myReaction = $value;
            }
        } else {
            if ($value == 1) $post->increment('likes_count');
            else $post->increment('dislikes_count');
            $post->reactions()->create(['visitor_hash' => $hash, 'value' => $value]);
            $myReaction = $value;
        }

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
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'nullable|email|max:150',
            'body' => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $post = Post::published()->findOrFail($postId);
        if (!$post->allow_comments) {
            return back()->with('error', 'Komentar dinonaktifkan untuk berita ini.');
        }

        if ($request->parent_id) {
            $parent = \App\Models\Comment::find($request->parent_id);
            if (! $parent || (int) $parent->post_id !== (int) $post->id) {
                return back()->withErrors(['body' => 'Komentar yang dibalas tidak ditemukan.']);
            }
            if ($parent->parent_id) {
                return back()->withErrors(['body' => 'Balasan hanya bisa ditujukan ke komentar utama.']);
            }
        }

        $comment = $post->comments()->create([
            'parent_id' => $request->parent_id,
            'name' => strip_tags($request->name),
            'email' => $request->email,
            'body' => strip_tags($request->body),
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
        $existing = $comment->reactions()->where('visitor_hash', $hash)->first();
        if ($existing) {
            $comment->decrement('likes_count');
            $existing->delete();
        } else {
            $comment->increment('likes_count');
            $comment->reactions()->create(['visitor_hash' => $hash]);
        }
        return response()->json(['likes' => $comment->fresh()->likes_count]);
    }

    public function share(Request $request, $postId)
    {
        Post::published()->findOrFail($postId)->increment('shares_count');
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
        $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:20',
            'subject' => 'nullable|string|max:200',
            'message' => 'required|string|max:2000',
        ]);
        \App\Models\ContactMessage::create([
            'name' => strip_tags($request->name),
            'email' => $request->email,
            'phone' => $request->phone,
            'subject' => $request->subject,
            'message' => strip_tags($request->message),
            'ip_hash' => hash('sha256', $request->ip()),
        ]);
        return back()->with('success', 'Pesan berhasil terkirim! Kami akan segera menghubungi Anda.');
    }

    public function testimonialStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'graduation_year' => 'required|integer|min:2000|max:' . (date('Y')),
            'occupation' => 'nullable|string|max:150',
            'workplace' => 'nullable|string|max:150',
            'message' => 'required|string|max:1000',
            'rating' => 'required|integer|min:1|max:5',
        ]);
        Testimonial::create([
            'name' => strip_tags($request->name),
            'graduation_year' => $request->graduation_year,
            'occupation' => $request->occupation,
            'workplace' => $request->workplace,
            'message' => strip_tags($request->message),
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
        $today = today();
        $path = $request->path();

        $unique = !Visit::where('visitor_hash', $hash)->whereDate('visited_on', $today)->exists();

        Visit::create([
            'url' => $request->url(),
            'path' => '/' . $path,
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
