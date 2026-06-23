<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale ?? 'de';
        app()->setLocale($locale);

        return $next($request);
    }
}
