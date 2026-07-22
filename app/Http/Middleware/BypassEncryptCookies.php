<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Development helper: bypass cookie encryption/decryption when OpenSSL is unavailable.
 *
 * IMPORTANT: This is a temporary, development-only workaround. It disables
 * cookie encryption which reduces confidentiality and integrity guarantees.
 * Revert this change after the underlying OpenSSL/WDAC issue is resolved.
 */
class BypassEncryptCookies
{
    public function handle(Request $request, Closure $next): Response
    {
        // No-op: do not attempt to decrypt incoming cookies or encrypt outgoing cookies.
        return $next($request);
    }
}
