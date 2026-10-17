<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tentukan IP pengunjung tanpa mempercayai X-Forwarded-For.
 *
 * CF-Connecting-IP hanya dipakai bila koneksi datang dari proxy terpercaya
 * (default: loopback, tempat cloudflared berjalan). Tanpa syarat ini, siapa pun
 * yang bisa menembak Nginx langsung dapat memalsukan IP untuk melewati rate
 * limit dan mengacaukan log aktivitas.
 */
class ClientIp
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isTrustedPeer((string) $request->server->get('REMOTE_ADDR', ''))) {
            $cf = trim((string) $request->headers->get('CF-Connecting-IP'));

            if ($cf !== '' && filter_var($cf, FILTER_VALIDATE_IP)) {
                $request->server->set('REMOTE_ADDR', $cf);
                $request->headers->set('X-Forwarded-For', $cf);
            }
        }

        return $next($request);
    }

    /** Daftar IP/CIDR proxy: config('app.trusted_proxies') (ENV TRUSTED_PROXY_IPS). */
    private function isTrustedPeer(string $ip): bool
    {
        if ($ip === '' || ! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        foreach ((array) config('app.trusted_proxies', ['127.0.0.1', '::1']) as $entry) {
            $entry = trim((string) $entry);
            if ($entry === '') {
                continue;
            }
            if (! str_contains($entry, '/')) {
                if ($entry === $ip) {
                    return true;
                }
                continue;
            }
            [$subnet, $bits] = explode('/', $entry, 2);
            $ipBin = @inet_pton($ip);
            $netBin = @inet_pton($subnet);
            $bits = (int) $bits;
            if ($ipBin === false || $netBin === false || strlen($ipBin) !== strlen($netBin) || $bits < 0 || $bits > strlen($ipBin) * 8) {
                continue;
            }
            $bytes = intdiv($bits, 8);
            $rest = $bits % 8;
            if (strncmp($ipBin, $netBin, $bytes) !== 0) {
                continue;
            }
            if ($rest === 0) {
                return true;
            }
            $mask = chr(0xFF << (8 - $rest) & 0xFF);
            if ((substr($ipBin, $bytes, 1) & $mask) === (substr($netBin, $bytes, 1) & $mask)) {
                return true;
            }
        }

        return false;
    }
}
