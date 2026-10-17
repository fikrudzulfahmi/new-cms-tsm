<?php

use App\Http\Controllers\Admin\CommentController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// ===================== PUBLIC =====================
Route::get('/', [HomeController::class, 'landing'])->name('landing');
Route::redirect('/beranda', '/', 301); // halaman beranda lama, digabung ke landing
Route::get('/berita', [HomeController::class, 'posts'])->name('posts');
Route::get('/berita/{slug}', [HomeController::class, 'post'])->name('post.show');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::post('/berita/{postId}/react', [HomeController::class, 'react'])->name('post.react')->middleware('throttle:20,1');
Route::post('/berita/{postId}/comment', [HomeController::class, 'comment'])->name('post.comment')->middleware('throttle:5,1');
Route::post('/berita/{postId}/share', [HomeController::class, 'share'])->name('post.share')->middleware('throttle:20,1');
Route::post('/komentar/{comment}/like', [HomeController::class, 'likeComment'])->name('comment.like')->middleware('throttle:20,1');
// SPMB memakai aplikasi sekolah sendiri: /spmb (dan link lama /ppdb) diarahkan ke Link SPMB di Admin → Pengaturan
Route::get('/spmb', [HomeController::class, 'spmb'])->name('spmb');
Route::get('/ppdb', [HomeController::class, 'spmb']);
Route::post('/kontak', [HomeController::class, 'contact'])->name('contact')->middleware('throttle:5,1');
Route::post('/testimoni', [HomeController::class, 'testimonialStore'])->name('testimonial.store')->middleware('throttle:5,1');

// ===================== AUTH =====================
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ===================== ADMIN =====================
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Posts
    Route::get('/berita', [PostController::class, 'index'])->name('posts.index');
    Route::get('/berita/buat', [PostController::class, 'create'])->name('posts.create');
    Route::post('/berita', [PostController::class, 'store'])->name('posts.store');
    Route::get('/berita/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::put('/berita/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/berita/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    Route::post('/berita/seo-analyze', [PostController::class, 'seoAnalyze'])->name('posts.seo');
    Route::get('/berita/{post}/analytics', [PostController::class, 'analytics'])->name('posts.analytics');

    // Comments
    Route::get('/komentar', [CommentController::class, 'index'])->name('comments.index');
    Route::patch('/komentar/{comment}/approve', [CommentController::class, 'approve'])->name('comments.approve');
    Route::patch('/komentar/{comment}/reject', [CommentController::class, 'reject'])->name('comments.reject');
    Route::patch('/komentar/{comment}/spam', [CommentController::class, 'spam'])->name('comments.spam');
    Route::delete('/komentar/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    // ===== Khusus admin (editor tidak boleh) =====
    Route::middleware('role:admin')->group(function () {

    // Reports
    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');

    // Settings
    Route::get('/pengaturan', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/pengaturan', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/pengaturan/upload', [SettingController::class, 'uploadImage'])->name('settings.upload');

    // Content
    Route::get('/slider', [ContentController::class, 'sliders'])->name('sliders.index');
    Route::post('/slider', [ContentController::class, 'slidersStore'])->name('sliders.store');
    Route::put('/slider/{slider}', [ContentController::class, 'slidersUpdate'])->name('sliders.update');
    Route::delete('/slider/{slider}', [ContentController::class, 'slidersDestroy'])->name('sliders.destroy');

    Route::get('/kompetensi', [ContentController::class, 'competencies'])->name('competencies.index');
    Route::post('/kompetensi', [ContentController::class, 'competenciesStore'])->name('competencies.store');
    Route::put('/kompetensi/{competency}', [ContentController::class, 'competenciesUpdate'])->name('competencies.update');
    Route::delete('/kompetensi/{competency}', [ContentController::class, 'competenciesDestroy'])->name('competencies.destroy');

    Route::get('/program', [ContentController::class, 'programs'])->name('programs.index');
    Route::post('/program', [ContentController::class, 'programsStore'])->name('programs.store');
    Route::put('/program/{program}', [ContentController::class, 'programsUpdate'])->name('programs.update');
    Route::delete('/program/{program}', [ContentController::class, 'programsDestroy'])->name('programs.destroy');

    Route::get('/sarana', [ContentController::class, 'facilities'])->name('facilities.index');
    Route::post('/sarana', [ContentController::class, 'facilitiesStore'])->name('facilities.store');
    Route::put('/sarana/{facility}', [ContentController::class, 'facilitiesUpdate'])->name('facilities.update');
    Route::delete('/sarana/{facility}', [ContentController::class, 'facilitiesDestroy'])->name('facilities.destroy');

    Route::get('/prestasi', [ContentController::class, 'achievements'])->name('achievements.index');
    Route::post('/prestasi', [ContentController::class, 'achievementsStore'])->name('achievements.store');
    Route::put('/prestasi/{achievement}', [ContentController::class, 'achievementsUpdate'])->name('achievements.update');
    Route::delete('/prestasi/{achievement}', [ContentController::class, 'achievementsDestroy'])->name('achievements.destroy');

    Route::get('/testimoni', [ContentController::class, 'testimonials'])->name('testimonials.index');
    Route::patch('/testimoni/{testimonial}', [ContentController::class, 'testimonialsUpdate'])->name('testimonials.update');
    Route::delete('/testimoni/{testimonial}', [ContentController::class, 'testimonialsDestroy'])->name('testimonials.destroy');

    Route::get('/guru', [ContentController::class, 'teachers'])->name('teachers.index');
    Route::post('/guru', [ContentController::class, 'teachersStore'])->name('teachers.store');
    Route::put('/guru/{teacher}', [ContentController::class, 'teachersUpdate'])->name('teachers.update');
    Route::delete('/guru/{teacher}', [ContentController::class, 'teachersDestroy'])->name('teachers.destroy');

    Route::get('/mitra', [ContentController::class, 'partners'])->name('partners.index');
    Route::post('/mitra', [ContentController::class, 'partnersStore'])->name('partners.store');
    Route::put('/mitra/{partner}', [ContentController::class, 'partnersUpdate'])->name('partners.update');
    Route::delete('/mitra/{partner}', [ContentController::class, 'partnersDestroy'])->name('partners.destroy');

    Route::get('/galeri', [ContentController::class, 'gallery'])->name('gallery.index');
    Route::post('/galeri', [ContentController::class, 'galleryStore'])->name('gallery.store');
    Route::put('/galeri/{gallery}', [ContentController::class, 'galleryUpdate'])->name('gallery.update');
    Route::delete('/galeri/{gallery}', [ContentController::class, 'galleryDestroy'])->name('gallery.destroy');

    Route::get('/kategori', [ContentController::class, 'categories'])->name('categories.index');
    Route::post('/kategori', [ContentController::class, 'categoriesStore'])->name('categories.store');
    Route::put('/kategori/{category}', [ContentController::class, 'categoriesUpdate'])->name('categories.update');
    Route::delete('/kategori/{category}', [ContentController::class, 'categoriesDestroy'])->name('categories.destroy');

    Route::get('/pesan', [ContentController::class, 'messages'])->name('messages.index');
    Route::patch('/pesan/{message}/read', [ContentController::class, 'messagesRead'])->name('messages.read');
    Route::delete('/pesan/{message}', [ContentController::class, 'messagesDestroy'])->name('messages.destroy');

    }); // end role:admin

});
