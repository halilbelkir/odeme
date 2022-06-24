<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StripAuthenticateHeader {

    // Enumerate unwanted headers
    private $unwantedHeaderList = [
        'X-Powered-By',
        'Server',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $this->removeUnwantedHeaders($this->unwantedHeaderList);
        return $response;
    }

    /**
     * @param $headerList
     */
    private function removeUnwantedHeaders($headerList)
    {
        foreach ($headerList as $header)
            header_remove($header);
    }
}
