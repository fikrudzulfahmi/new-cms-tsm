<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tentukan IP pengunjung tanpa mempercayai X-Forwarded-For.
 * Cloudflare Tunnel mengisi CF-Connecting-IP dengan IP asli pengunjung.
 */
class ClientIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $cf = trim((string) $request->headers->get('CF-Connecting-IP'));

        if ($cf !== '' && filter_var($cf, FILTER_VALIDATE_IP)) {
            $request->server->set('REMOTE_ADDR', $cf);
        }

        return $next($request);
    }
}
