<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ForceMindArHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        // Ngrok terminates HTTPS before forwarding the request to local HTTP.
        // Only MindAR needs HTTPS URLs for phone camera access.
        $forwardedScheme = strtolower(trim(explode(',', $request->header('X-Forwarded-Proto', ''))[0]));

        if (! $request->isSecure() && $forwardedScheme !== 'https') {
            return $next($request);
        }

        URL::forceScheme('https');

        try {
            return $next($request);
        } finally {
            URL::forceScheme(null);
        }
    }
}
