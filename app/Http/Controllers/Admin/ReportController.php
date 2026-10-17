<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ], [
            'from.date' => 'Tanggal awal tidak valid.',
            'to.date' => 'Tanggal akhir tidak valid.',
            'to.after_or_equal' => 'Tanggal akhir tidak boleh lebih awal dari tanggal awal.',
        ]);

        $from = ! empty($validated['from']) ? Carbon::parse($validated['from'])->startOfDay() : now()->subDays(29)->startOfDay();
        $to   = ! empty($validated['to'])   ? Carbon::parse($validated['to'])->endOfDay()     : now()->endOfDay();

        // Batasi rentang agar laporan tidak membebani database.
        if ($from->diffInDays($to) > 366) {
            $from = $to->copy()->subDays(366);
        }

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
}
