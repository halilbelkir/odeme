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
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Strict-Transport-Security', 'max-age:31536000; includeSubDomains');
        $response->headers->set('Public-Key-Pins', 'pin-sha256="base64=' . env('SUBJECT_PUBLIC_KEY_INFORMATION_FINGERPRINT') . '"; max-age=31536000; includeSubDomains');
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
