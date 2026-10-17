<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Competency;
use App\Models\ContactMessage;
use App\Models\Facility;
use App\Models\Gallery;
use App\Models\Partner;
use App\Models\Post;
use App\Models\Program;
use App\Models\Setting;
use App\Models\Teacher;
use App\Models\Testimonial;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    /** Rentang tanggal laporan (default 30 hari terakhir, maksimal 366 hari). */
    private function range(Request $request): array
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ], [
            'from.date' => 'Tanggal awal tidak valid.',
            'to.date' => 'Tanggal akhir tidak valid.',
            'to.after_or_equal' => 'Tanggal akhir tidak boleh lebih awal dari tanggal awal.',
        ]);

        $from = ! empty($validated['from']) ? Carbon::parse($validated['from'], 'Asia/Jakarta')->startOfDay() : now('Asia/Jakarta')->subDays(29)->startOfDay();
        $to   = ! empty($validated['to'])   ? Carbon::parse($validated['to'], 'Asia/Jakarta')->endOfDay()     : now('Asia/Jakarta')->endOfDay();

        // Batasi rentang agar laporan tidak membebani database.
        if ($from->diffInDays($to) > 366) {
            $from = $to->copy()->subDays(366);
        }

        return [$from, $to];
    }

    public function index(Request $request)
    {
        [$from, $to] = $this->range($request);

        $visitChart = Visit::selectRaw('DATE(visited_on) as date, COUNT(*) as total, SUM(is_unique) as unique_v')
            ->whereBetween('visited_on', [$from->toDateString(), $to->toDateString()])
            ->groupBy('date')->orderBy('date')->get();

        $topPosts = Post::published()->with('category')
            ->orderByDesc('views_count')->take(10)
            ->get(['id','title','slug','views_count','likes_count','dislikes_count','shares_count','comments_count','seo_score','category_id','published_at']);

        $browserStats = Visit::selectRaw('browser, COUNT(*) as count')
            ->whereBetween('visited_on', [$from->toDateString(), $to->toDateString()])
            ->groupBy('browser')->orderByDesc('count')->get();

        $deviceStats = Visit::selectRaw('device, COUNT(*) as count')
            ->whereBetween('visited_on', [$from->toDateString(), $to->toDateString()])
            ->groupBy('device')->orderByDesc('count')->get();

        $referrerStats = Visit::selectRaw('referrer, COUNT(*) as count')
            ->whereBetween('visited_on', [$from->toDateString(), $to->toDateString()])
            ->whereNotNull('referrer')->where('referrer','!=','')
            ->groupBy('referrer')->orderByDesc('count')->take(10)->get();

        $postCreatedChart = Post::selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('date')->orderBy('date')->get();

        return Inertia::render('Admin/Reports/Index', [
            'stats' => [
                'total_visits' => Visit::count(),
                'total_posts' => Post::count(),
                'total_comments' => \App\Models\Comment::count(),
                'total_messages' => \App\Models\ContactMessage::count(),
            ],
            'categoryStats' => \App\Models\Category::withCount('posts')->orderByDesc('posts_count')->get(),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'summary' => [
                'total_visits' => Visit::whereBetween('visited_on', [$from->toDateString(), $to->toDateString()])->count(),
                'unique_visits' => Visit::whereBetween('visited_on', [$from->toDateString(), $to->toDateString()])->where('is_unique',true)->count(),
                'total_posts_created' => Post::whereBetween('created_at', [$from, $to])->count(),
                'total_published' => Post::where('status','published')->whereBetween('published_at', [$from, $to])->count(),
            ],
            'visitChart' => $visitChart,
            'topPosts' => $topPosts,
            'browserStats' => $browserStats,
            'deviceStats' => $deviceStats,
            'referrerStats' => $referrerStats,
            'postCreatedChart' => $postCreatedChart,
        ]);
    }

    /**
     * Laporan siap cetak (A4) untuk diserahkan ke atasan.
     * Format PDF dihasilkan lewat dialog cetak browser: "Simpan sebagai PDF".
     */
    public function print(Request $request)
    {
        [$from, $to] = $this->range($request);
        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();
        $days = max(1, $from->diffInDays($to) + 1);

        $visitQuery = Visit::whereBetween('visited_on', [$fromDate, $toDate]);

        $summary = [
            'visits' => (clone $visitQuery)->count(),
            'unique' => (clone $visitQuery)->where('is_unique', true)->count(),
            'per_day' => round((clone $visitQuery)->count() / $days, 1),
            'posts_published' => Post::where('status', 'published')->whereBetween('published_at', [$from, $to])->count(),
            'comments' => Comment::whereBetween('created_at', [$from, $to])->count(),
            'messages' => ContactMessage::whereBetween('created_at', [$from, $to])->count(),
        ];

        $topPages = Visit::selectRaw('path, COUNT(*) as total')
            ->whereBetween('visited_on', [$fromDate, $toDate])
            ->whereNotNull('path')->where('path', '!=', '')
            ->groupBy('path')->orderByDesc('total')->take(8)->get();

        $topPosts = Post::query()
            ->whereIn('id', Visit::select('post_id')->whereNotNull('post_id')->whereBetween('visited_on', [$fromDate, $toDate]))
            ->orderByDesc('views_count')->take(5)
            ->get(['id', 'title', 'views_count', 'published_at']);

        $posts = Post::with(['user:id,name', 'category:id,name'])
            ->where('status', 'published')
            ->whereBetween('published_at', [$from, $to])
            ->orderBy('published_at')
            ->get(['id', 'title', 'excerpt', 'slug', 'user_id', 'category_id', 'published_at', 'views_count']);

        // Pembaruan website: hanya aksi yang mengubah data (tambah/ubah/hapus).
        $activities = ActivityLog::whereBetween('created_at', [$from, $to])
            ->whereIn('action', ['create', 'update', 'delete'])
            ->orderBy('created_at')
            ->get(['id', 'user_name', 'role', 'action', 'module', 'description', 'created_at']);

        $activityRecap = $activities
            ->groupBy('module')
            ->map(fn ($rows, $module) => [
                'label' => $this->moduleLabelForReport($module),
                'create' => $rows->where('action', 'create')->count(),
                'update' => $rows->where('action', 'update')->count(),
                'delete' => $rows->where('action', 'delete')->count(),
                'total' => $rows->count(),
            ])
            ->sortByDesc('total');

        $contentTotals = [
            ['label' => 'Berita terbit', 'total' => Post::where('status', 'published')->count()],
            ['label' => 'Guru & instruktur', 'total' => Teacher::count()],
            ['label' => 'Kompetensi', 'total' => Competency::count()],
            ['label' => 'Program unggulan', 'total' => Program::count()],
            ['label' => 'Sarana', 'total' => Facility::count()],
            ['label' => 'Prestasi', 'total' => Achievement::count()],
            ['label' => 'Testimoni / umpan balik lulusan', 'total' => Testimonial::count()],
            ['label' => 'Mitra industri', 'total' => Partner::count()],
            ['label' => 'Galeri', 'total' => Gallery::count()],
            ['label' => 'Kategori berita', 'total' => Category::count()],
        ];

        $settings = [
            'school_name' => Setting::get('school_name', 'SMKS Islam 1 Kota Blitar'),
            'department_name' => Setting::get('department_name', 'Teknik Sepeda Motor'),
            'address' => Setting::get('address', ''),
            'phone' => Setting::get('phone', ''),
            'email' => Setting::get('email', ''),
            'site_logo' => Setting::get('site_logo'),
            'profile_head_name' => Setting::get('profile_head_name'),
        ];

        return view('admin.reports.print', [
            'from' => $from,
            'to' => $to,
            'days' => $days,
            'summary' => $summary,
            'topPages' => $topPages,
            'topPosts' => $topPosts,
            'posts' => $posts,
            'activities' => $activities,
            'activityRecap' => $activityRecap,
            'contentTotals' => $contentTotals,
            'settings' => $settings,
            'logSince' => ActivityLog::min('created_at'),
            'printedBy' => Auth::user()?->name ?? 'Administrator',
            'printedAt' => now('Asia/Jakarta'),
        ]);
    }

    /** Label bagian aktivitas yang lebih mudah dibaca atasan. */
    private function moduleLabelForReport(?string $module): string
    {
        return match ($module) {
            'testimonials' => 'Testimoni / umpan balik lulusan',
            'teachers' => 'Guru & instruktur',
            'achievements' => 'Prestasi',
            'programs' => 'Program unggulan',
            'facilities' => 'Sarana & fasilitas',
            'competencies' => 'Kompetensi',
            'gallery' => 'Galeri',
            'sliders' => 'Slider beranda',
            'partners' => 'Mitra industri',
            'categories' => 'Kategori berita',
            'posts' => 'Berita',
            'comments' => 'Komentar',
            'messages' => 'Pesan masuk',
            'settings' => 'Pengaturan situs',
            default => ActivityLog::moduleLabel($module),
        };
    }
}
