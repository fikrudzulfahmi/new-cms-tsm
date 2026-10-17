<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
