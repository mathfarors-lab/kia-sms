<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale');

        if (!$locale && auth()->check()) {
            $locale = auth()->user()->locale;
        }

        if ($locale && in_array($locale, ['en', 'km'])) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
