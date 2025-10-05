<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::debug('CorsMiddleware');

        $allowHeaders = $request->headers->get('Access-Control-Request-Headers', '*');
        $headers = [
            'Access-Control-Allow-Origin'      => '*', // (Nếu dùng cookie/credentials: không dùng *, phải echo origin hợp lệ và thêm Allow-Credentials: true)
            'Access-Control-Allow-Methods'     => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers'     => $allowHeaders,
            'Access-Control-Max-Age'           => '3600',
            'Vary'                             => 'Origin, Access-Control-Request-Method, Access-Control-Request-Headers',
        ];

        // ✅ Preflight: trả 204 ngay, KHÔNG gọi $next()
        if ($request->isMethod('OPTIONS')) {
            return response('', 204)->withHeaders($headers);
        }

        /** @var Response $response */
        $response = $next($request);

        foreach ($headers as $k => $v) {
            $response->headers->set($k, $v);
        }

        return $response;
    }
}
