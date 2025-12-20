<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowHttp
{
    /**
     * Handle an incoming request.
     * This middleware allows both HTTP and HTTPS requests to pass through.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Simply pass the request through without forcing any scheme
        // This allows both HTTP and HTTPS
        return $next($request);
    }
}
