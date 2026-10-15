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
        $from = $request->from ? Carbon::parse($request->from) : now()->subDays(29);
        $to   = $request->to   ? Carbon::parse($request->to)   : now();

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
                'total_ppdb' => \App\Models\Registration::count(),
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
