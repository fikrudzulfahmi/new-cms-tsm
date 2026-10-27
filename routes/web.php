<?php

use App\Http\Controllers\Admin\CommentController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\HomeByRoleController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// ===================== PUBLIC =====================
Route::get('/', [HomeController::class, 'landing'])->name('landing');
Route::redirect('/beranda', '/', 301); // halaman beranda lama, digabung ke landing
Route::get('/berita', [HomeController::class, 'posts'])->name('posts');
Route::get('/berita/{slug}', [HomeController::class, 'post'])->name('post.show');
// Halaman per bagian (dulu hanya section di landing)
Route::get('/profil', [HomeController::class, 'profile'])->name('profile');
Route::get('/guru', [HomeController::class, 'teachers'])->name('teachers');
Route::get('/kompetensi', [HomeController::class, 'competencies'])->name('competencies');
Route::get('/kompetensi/{slug}', [HomeController::class, 'competency'])->name('competency.show');
Route::get('/program', [HomeController::class, 'programs'])->name('programs');
Route::get('/sarana', [HomeController::class, 'facilities'])->name('facilities');
Route::get('/prestasi', [HomeController::class, 'achievements'])->name('achievements');
Route::get('/alumni', [HomeController::class, 'alumni'])->name('alumni');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::post('/berita/{postId}/react', [HomeController::class, 'react'])->name('post.react')->middleware('throttle:10,1');
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

// Ganti password (wajib pada login pertama) & halaman awal per role.
Route::middleware('auth')->group(function () {
    Route::get('/pilih', function () {
        $u = auth()->user();
        if (! $u->isContributor()) {
            return redirect($u->homeUrl());
        }

        return \Inertia\Inertia::render('Auth/Choose', ['lms' => $u->role === 'guru' ? '/ruang-guru' : '/belajar']);
    })->name('choose');
    Route::get('/ganti-password', [PasswordController::class, 'edit'])->name('password.change');
    Route::put('/ganti-password', [PasswordController::class, 'update'])->name('password.update')->middleware('throttle:10,1');
    Route::prefix('ruang-guru')->middleware('role:guru')->name('guru.')->group(function () {
        $c = \App\Http\Controllers\Guru\CourseController::class;
        $a = \App\Http\Controllers\Guru\AttendanceController::class;
        Route::get('/presensi', [$a, 'index'])->name('att.index');
        Route::post('/presensi', [$a, 'store'])->name('att.store');
        Route::get('/presensi/{session}', [$a, 'show'])->name('att.show');
        Route::put('/presensi/{session}', [$a, 'update'])->name('att.update');
        Route::delete('/presensi/{session}', [$a, 'destroy'])->name('att.destroy');
        Route::get('/presensi/{session}/foto/{student}', [$a, 'photo'])->name('att.photo');
        Route::get('/presensi/{session}/ekspor', [$a, 'export'])->name('att.export');
        Route::post('/presensi/{session}/siswa/{student}', [$a, 'setRecord'])->name('att.set');
        $q = \App\Http\Controllers\Guru\QuestionController::class;
        $z = \App\Http\Controllers\Guru\QuizController::class;
        Route::get('/mapel/{course}/soal', [$q, 'index'])->name('questions');
        Route::post('/mapel/{course}/soal', [$q, 'store'])->name('questions.store');
        Route::post('/mapel/{course}/soal/impor', [$q, 'importPreview'])->middleware('throttle:10,1')->name('questions.import');
        Route::post('/mapel/{course}/soal/impor/simpan', [$q, 'importCommit'])->name('questions.commit');
        Route::put('/soal/{question}', [$q, 'update'])->name('questions.update');
        Route::delete('/soal/{question}', [$q, 'destroy'])->name('questions.destroy');
        Route::get('/mapel/{course}/tugas', [\App\Http\Controllers\Guru\AssignmentController::class, 'index'])->name('assignments');
        Route::post('/mapel/{course}/tugas', [\App\Http\Controllers\Guru\AssignmentController::class, 'store'])->name('assignments.store');
        Route::get('/tugas/{assignment}', [\App\Http\Controllers\Guru\AssignmentController::class, 'show'])->name('assignment');
        Route::put('/tugas/{assignment}', [\App\Http\Controllers\Guru\AssignmentController::class, 'update'])->name('assignment.update');
        Route::delete('/tugas/{assignment}', [\App\Http\Controllers\Guru\AssignmentController::class, 'destroy'])->name('assignment.destroy');
        Route::post('/pengumpulan/{submission}/nilai', [\App\Http\Controllers\Guru\AssignmentController::class, 'grade'])->name('submission.grade');
        Route::delete('/pengumpulan/{submission}/berkas', [\App\Http\Controllers\Guru\AssignmentController::class, 'deleteFile'])->name('submission.file');
        Route::patch('/tugas/{assignment}/tayang', [\App\Http\Controllers\Guru\AssignmentController::class, 'togglePublished'])->name('assignment.publish');
        Route::delete('/tugas/{assignment}/semua-berkas', [\App\Http\Controllers\Guru\AssignmentController::class, 'deleteAllFiles'])->name('assignment.files');
        Route::get('/tugas/{assignment}/lampiran', [\App\Http\Controllers\Guru\AssignmentController::class, 'brief'])->name('assignment.brief');
        Route::get('/berkas-pengumpulan/{file}', [\App\Http\Controllers\Guru\AssignmentController::class, 'download'])->name('submission.download');
        Route::get('/mapel/{course}/buku-nilai', [\App\Http\Controllers\Guru\GradebookController::class, 'index'])->name('gradebook');
        Route::get('/mapel/{course}/buku-nilai/ekspor', [\App\Http\Controllers\Guru\GradebookController::class, 'export'])->name('gradebook.export');
        Route::get('/mapel/{course}/quiz', [$z, 'index'])->name('quizzes');
        Route::post('/mapel/{course}/quiz', [$z, 'store'])->name('quiz.store');
        Route::get('/quiz/{quiz}', [$z, 'show'])->name('quiz.show');
        Route::put('/quiz/{quiz}', [$z, 'update'])->name('quiz.update');
        Route::delete('/quiz/{quiz}', [$z, 'destroy'])->name('quiz.destroy');
        Route::post('/quiz/{quiz}/soal', [$z, 'setQuestions'])->name('quiz.questions');
        Route::get('/pengerjaan/{attempt}', [$z, 'attempt'])->name('quiz.attempt');
        Route::post('/pengerjaan/{attempt}/soal/{question}', [$z, 'grade'])->name('quiz.grade');
        Route::get('/', [$c, 'index'])->name('home');
        Route::get('/mapel/{course}', [$c, 'show'])->name('course');
        Route::get('/mapel/{course}/progres', [$c, 'progress'])->name('progress');
        Route::post('/mapel/{course}/bab', [$c, 'storeSection'])->name('section.store');
        Route::put('/bab/{section}', [$c, 'updateSection'])->name('section.update');
        Route::delete('/bab/{section}', [$c, 'destroySection'])->name('section.destroy');
        Route::post('/bab/{section}/geser', [$c, 'moveSection'])->name('section.move');
        Route::post('/bab/{section}/materi', [$c, 'storeLesson'])->name('lesson.store');
        Route::post('/materi/{lesson}', [$c, 'updateLesson'])->name('lesson.update');   // POST agar multipart berfungsi
        Route::delete('/materi/{lesson}', [$c, 'destroyLesson'])->name('lesson.destroy');
    });
    Route::prefix('belajar')->middleware('role:siswa')->name('siswa.')->group(function () {
        $c = \App\Http\Controllers\Siswa\LearnController::class;
        $a = \App\Http\Controllers\Siswa\AttendanceController::class;
        Route::get('/presensi', [$a, 'index'])->name('att.index');
        Route::post('/presensi/{session}', [$a, 'mark'])->middleware('throttle:20,1')->name('att.mark');
        $z = \App\Http\Controllers\Siswa\QuizController::class;
        Route::get('/tugas/{assignment}', [\App\Http\Controllers\Siswa\AssignmentController::class, 'show'])->name('assignment');
        Route::post('/tugas/{assignment}', [\App\Http\Controllers\Siswa\AssignmentController::class, 'submit'])->middleware('throttle:20,1')->name('assignment.submit');
        Route::get('/tugas-berkas/{file}', [\App\Http\Controllers\Siswa\AssignmentController::class, 'download'])->name('assignment.file');
        Route::get('/tugas/{assignment}/lampiran', [\App\Http\Controllers\Siswa\AssignmentController::class, 'brief'])->name('assignment.brief');
        Route::get('/quiz/{quiz}', [$z, 'show'])->name('quiz.show');
        Route::post('/quiz/{quiz}/mulai', [$z, 'start'])->middleware('throttle:20,1')->name('quiz.start');
        Route::get('/pengerjaan/{attempt}', [$z, 'attempt'])->name('quiz.attempt');
        Route::put('/pengerjaan/{attempt}/soal/{question}', [$z, 'save'])->middleware('throttle:240,1')->name('quiz.save');
        Route::post('/pengerjaan/{attempt}/tab', [$z, 'tab'])->middleware('throttle:60,1')->name('quiz.tab');
        Route::post('/pengerjaan/{attempt}/kumpul', [$z, 'submit'])->name('quiz.submit');
        Route::get('/pengerjaan/{attempt}/hasil', [$z, 'result'])->name('quiz.result');
        Route::get('/', [$c, 'index'])->name('home');
        Route::get('/mapel/{course}', [$c, 'course'])->name('course');
        Route::get('/materi/{lesson}', [$c, 'lesson'])->name('lesson');
        Route::post('/materi/{lesson}/selesai', [$c, 'complete'])->name('lesson.complete');
    });
    Route::get('/berkas-materi/{lesson}', [\App\Http\Controllers\Siswa\LearnController::class, 'file'])
        ->middleware('role:siswa,guru')->name('lesson.file');
    Route::prefix('superadmin')->middleware('role:superadmin')->name('superadmin.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Superadmin\DashboardController::class, 'index'])->name('home');
        Route::post('/tahun-ajaran', [\App\Http\Controllers\Superadmin\DashboardController::class, 'storeYear'])->name('year.store');
        Route::post('/tahun-ajaran/{year}/aktifkan', [\App\Http\Controllers\Superadmin\DashboardController::class, 'activateYear'])->name('year.activate');
        Route::delete('/tahun-ajaran/{year}', [\App\Http\Controllers\Superadmin\DashboardController::class, 'destroyYear'])->name('year.destroy');
        $ind = \App\Http\Controllers\Superadmin\InductController::class;
        Route::get('/siswa', [$ind, 'students'])->name('students');
        Route::get('/guru', [$ind, 'teachers'])->name('teachers');
        Route::post('/kelas/tarik', [$ind, 'pullClasses'])->middleware('throttle:6,1')->name('classes.pull');
        Route::post('/kelas/{class}/pratinjau', [$ind, 'preview'])->middleware('throttle:20,1')->name('class.preview');
        Route::post('/kelas/{class}/tarik', [$ind, 'pull'])->middleware('throttle:20,1')->name('class.pull');
        Route::post('/guru', [\App\Http\Controllers\Superadmin\DashboardController::class, 'storeTeacher'])->name('teacher.store');
        Route::get('/penyimpanan', [\App\Http\Controllers\Superadmin\StorageController::class, 'index'])->name('storage');
        Route::put('/penyimpanan', [\App\Http\Controllers\Superadmin\StorageController::class, 'update'])->name('storage.update');
        Route::delete('/penyimpanan/{file}', [\App\Http\Controllers\Superadmin\StorageController::class, 'destroy'])->name('storage.destroy');
        Route::get('/mapel', [\App\Http\Controllers\Superadmin\CourseController::class, 'index'])->name('courses');
        Route::post('/mapel', [\App\Http\Controllers\Superadmin\CourseController::class, 'store'])->name('courses.store');
        Route::put('/mapel/{course}', [\App\Http\Controllers\Superadmin\CourseController::class, 'update'])->name('courses.update');
        Route::delete('/mapel/{course}', [\App\Http\Controllers\Superadmin\CourseController::class, 'destroy'])->name('courses.destroy');
        Route::post('/akun/{user}/reset', [\App\Http\Controllers\Superadmin\DashboardController::class, 'resetPassword'])->name('user.reset');
        Route::post('/akun/{user}/penulis', [\App\Http\Controllers\Superadmin\DashboardController::class, 'toggleWriter'])->name('user.writer');
        Route::post('/kelas/{class}/penulis', [\App\Http\Controllers\Superadmin\DashboardController::class, 'classWriters'])->name('class.writers');
        Route::post('/akun/{user}/aktif', [\App\Http\Controllers\Superadmin\DashboardController::class, 'toggleActive'])->name('user.toggle');
    });
});

// ===================== ADMIN =====================
// activity.log dipasang paling depan agar penolakan auth/admin/role ikut tercatat.
Route::prefix('admin')->name('admin.')->middleware(['activity.log', 'auth', 'admin'])->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Posts
    Route::get('/berita', [PostController::class, 'index'])->name('posts.index');
    Route::get('/berita/buat', [PostController::class, 'create'])->name('posts.create');
    Route::post('/berita', [PostController::class, 'store'])->name('posts.store');
    Route::get('/berita/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::put('/berita/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/berita/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    Route::patch('/berita/{post}/setujui', [PostController::class, 'approve'])->name('posts.approve')->middleware('role:admin');
    Route::post('/berita/seo-analyze', [PostController::class, 'seoAnalyze'])->name('posts.seo');
    Route::get('/berita/{post}/analytics', [PostController::class, 'analytics'])->name('posts.analytics');

    // Comments
    Route::get('/komentar', [CommentController::class, 'index'])->name('comments.index');
    Route::patch('/komentar/{comment}/approve', [CommentController::class, 'approve'])->name('comments.approve');
    Route::patch('/komentar/{comment}/reject', [CommentController::class, 'reject'])->name('comments.reject');
    Route::patch('/komentar/{comment}/spam', [CommentController::class, 'spam'])->name('comments.spam');
    Route::delete('/komentar/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    // Upload gambar editor (dipakai RichEditor) — editor juga butuh, jadi di luar grup role:admin.
    Route::post('/pengaturan/upload', [SettingController::class, 'uploadImage'])->name('settings.upload')->middleware('throttle:20,1');

    // ===== Khusus admin (editor tidak boleh) =====
    Route::middleware('role:admin')->group(function () {

    // Reports
    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/laporan/cetak', [ReportController::class, 'print'])->name('reports.print');

    // Log aktivitas + deteksi gangguan keamanan
    Route::get('/aktivitas', [ActivityController::class, 'index'])->name('activity.index');

    // Settings
    Route::get('/pengaturan', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/pengaturan', [SettingController::class, 'update'])->name('settings.update');
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
    Route::match(['put', 'patch'], '/testimoni/{testimonial}', [ContentController::class, 'testimonialsUpdate'])->name('testimonials.update');
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
