<?php

// namespace App\Http\Middleware;

// protected $routeMiddleware = [
//     // ...
//     // 'throttle' => \App\Http\Middleware\RateLimitMiddleware::class,
//     // 'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
//     // 'rate_limit_requests' => \App\Http\Middleware\RateLimitRequests::class,
//     'apikey' => \App\Http\Middleware\ApiKeyMiddleware::class,
// ];

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $routeMiddleware = [
        // ...
        // 'throttle' => \App\Http\Middleware\RateLimitMiddleware::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'rate_limit_requests' => \App\Http\Middleware\RateLimitRequests::class,
        'apikey' => \App\Http\Middleware\ApiKeyMiddleware::class,
    ];
}
