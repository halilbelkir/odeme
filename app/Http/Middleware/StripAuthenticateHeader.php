<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StripAuthenticateHeader {

    public function handle($request, Closure $next)
    {
        $request->headers->remove('www-authenticate');
        return $next($request);
    }
}
