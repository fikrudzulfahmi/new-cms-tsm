<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        $query = Comment::with('post:id,title,slug')->latest();
        if ($request->status) $query->where('status', $request->status);
        return Inertia::render('Admin/Comments/Index', [
            'comments' => $query->paginate(20)->withQueryString()
                ->through(fn (Comment $comment) => $comment->makeVisible('email')),
            'filters' => $request->only(['status']),
            'counts' => [
                'pending' => Comment::pending()->count(),
                'approved' => Comment::approved()->count(),
                'spam' => Comment::where('status','spam')->count(),
            ],
        ]);
    }
    public function approve(Comment $comment)
    {
        $comment->update(['status' => 'approved']);
        return back()->with('success', 'Komentar disetujui.');
    }
    public function reject(Comment $comment)
    {
        $comment->update(['status' => 'rejected']);
        return back()->with('success', 'Komentar ditolak.');
    }
    public function spam(Comment $comment)
    {
        $comment->update(['status' => 'spam']);
        return back()->with('success', 'Komentar ditandai spam.');
    }
    public function destroy(Comment $comment)
    {
        $comment->delete();
        return back()->with('success', 'Komentar dihapus.');
    }
}
