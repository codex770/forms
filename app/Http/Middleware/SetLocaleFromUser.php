<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $locale = in_array($user->locale, ['de', 'en']) ? $user->locale : 'de';
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
