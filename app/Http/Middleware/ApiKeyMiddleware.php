<?php

namespace App\Http\Middleware;

use App\Exceptions\ComicException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->get('api_key')) {
            throw new ComicException(status: false, message: 'Missing api_key param!');
        }

        if ($request->get('api_key') != config('comic.api_key')) {
            throw new ComicException(status: false, message: 'API Key Invalid!');
        }

        return $next($request);
    }
}
