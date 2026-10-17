<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Jangan percaya header proxy apa pun: X-Forwarded-For bisa dipalsukan klien
        // untuk mem-bypass rate limit dan memalsukan kunjungan unik.
        // IP asli diambil dari CF-Connecting-IP (diisi Cloudflare) oleh ClientIp.
        $middleware->trustProxies(at: []);
        $middleware->prepend(\App\Http\Middleware\ClientIp::class);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()?->homeUrl() ?? '/');
        $middleware->redirectGuestsTo('/login');
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'activity.log' => \App\Http\Middleware\LogAdminActivity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Halaman galat memakai desain sendiri (resources/views/errors + Pages/Error.vue),
        // bukan tampilan bawaan Laravel, agar tetap senada dengan landing page.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $status = $response->getStatusCode();

            $pages = [
                403 => ['label' => 'Akses ditolak', 'heading' => 'Anda tidak punya izin membuka halaman ini', 'message' => 'Halaman ini khusus untuk pengelola website. Jika Anda admin, silakan masuk kembali dengan akun yang benar.'],
                404 => ['label' => 'Halaman tidak ditemukan', 'heading' => 'Halaman yang Anda cari tidak ada', 'message' => 'Alamatnya mungkin salah ketik, atau halaman sudah dipindahkan. Silakan mulai lagi dari beranda atau lihat berita terbaru kami.'],
                405 => ['label' => 'Metode tidak diizinkan', 'heading' => 'Permintaan ini tidak bisa diproses', 'message' => 'Halaman menerima jenis permintaan yang berbeda. Kembali ke beranda lalu ulangi dari menu yang tersedia.'],
                419 => ['label' => 'Sesi berakhir', 'heading' => 'Halaman terlalu lama terbuka', 'message' => 'Demi keamanan, sesi Anda sudah berakhir. Muat ulang halaman lalu masuk kembali untuk melanjutkan.'],
                429 => ['label' => 'Terlalu banyak permintaan', 'heading' => 'Mohon tunggu sebentar', 'message' => 'Anda mengirim terlalu banyak permintaan dalam waktu singkat. Tunggu beberapa menit, lalu coba lagi.'],
                500 => ['label' => 'Kesalahan server', 'heading' => 'Ada gangguan di server kami', 'message' => 'Permintaan Anda gagal diproses karena kendala teknis. Silakan coba lagi beberapa saat lagi; bila berulang, hubungi pengelola website.'],
                503 => ['label' => 'Sedang pemeliharaan', 'heading' => 'Website sedang diperbarui', 'message' => 'Kami sedang melakukan pemeliharaan singkat. Silakan kembali beberapa menit lagi — terima kasih atas kesabarannya.'],
            ];

            if (! isset($pages[$status])) {
                return $response;
            }

            // Navigasi Inertia di dalam aplikasi: tampilkan halaman Vue yang sama desainnya.
            if ($request->isMethod('GET') && $request->header('X-Inertia') && ! $request->expectsJson()) {
                try {
                    return Inertia::render('Error', ['status' => $status] + $pages[$status])
                        ->toResponse($request)
                        ->setStatusCode($status);
                } catch (\Throwable) {
                    return $response;
                }
            }

            return $response;
        });
    })->create();
