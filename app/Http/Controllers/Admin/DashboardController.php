<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\Category;
use App\Models\Comment;
use App\Models\ContactMessage;
use App\Models\Post;
use App\Models\Testimonial;
use App\Models\Visit;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // Statistik kunjungan memakai tanggal WIB (data visits disimpan per tanggal WIB).
        $tz = 'Asia/Jakarta';
        $today = now($tz)->toDateString();
        $thisMonth = [now($tz)->startOfMonth(), now($tz)->endOfMonth()];

        // Visit chart 30 hari
        $visitChart = Visit::selectRaw('DATE(visited_on) as date, COUNT(*) as total, SUM(is_unique) as unique_visitors')
            ->where('visited_on', '>=', now($tz)->subDays(29)->toDateString())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $days = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = now($tz)->subDays($i)->toDateString();
            $days[] = [
                'date' => $d,
                'label' => Carbon::parse($d)->isoFormat('D MMM'),
                'total' => $visitChart[$d]->total ?? 0,
                'unique' => $visitChart[$d]->unique_visitors ?? 0,
            ];
        }

        // Top posts
        $topPosts = Post::published()->orderByDesc('views_count')->take(5)->get(['id', 'title', 'views_count', 'likes_count', 'comments_count', 'slug']);

        // Device distribution
        $devices = Visit::selectRaw('device, COUNT(*) as count')
            ->whereBetween('visited_on', $thisMonth)
            ->whereNotNull('device')
            ->groupBy('device')->get();

        return Inertia::render('Admin/Dashboard/Index', [
            'recentPosts' => Post::latest()->take(5)->get(['id','title','status','views_count','published_at']),
            'stats' => [
                'total_visits' => Visit::count(),
                'total_posts' => Post::count(),
                'total_achievements' => Achievement::count(),
                'posts_total' => Post::count(),
                'posts_published' => Post::where('status', 'published')->count(),
                'posts_pending' => Post::where('status', 'pending')->count(),
                'posts_draft' => Post::where('status', 'draft')->count(),
                'visits_today' => Visit::whereDate('visited_on', $today)->count(),
                'visits_month' => Visit::whereBetween('visited_on', $thisMonth)->count(),
                'unique_today' => Visit::whereDate('visited_on', $today)->where('is_unique', true)->count(),
                'comments_pending' => Comment::pending()->count(),
                'achievements_total' => Achievement::count(),
                'messages_unread' => ContactMessage::where('is_read', false)->count(),
                'testimonials_pending' => Testimonial::where('status', 'pending')->count(),
            ],
            'visitChart' => $days,
            'topPosts' => $topPosts,
            'deviceDistribution' => $devices,
            'recentComments' => Comment::with('post:id,title,slug')->latest()->take(5)->get(),
        ]);
    }
}
