<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        // Configure BOTH directions together for Laravel 13.
        // An authenticated Owner opening /secretkya must reach /owner,
        // never the public homepage (the framework's default guest redirect).
        $middleware->redirectTo(
            guests: fn (Request $request) => route('login'),
            users: fn (Request $request) => route('owner.dashboard'),
        );
        $middleware->validateCsrfTokens(except: []);

        // Do not call config() here: the Laravel configuration binding has not
        // been registered when this callback is invoked by the kernel.
        // The default TrustProxies middleware reads `trustedproxy.proxies`
        // when handling each request, AFTER the app has bootstrapped.
        $middleware->trustProxies(
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Default Laravel exception handling.
    })->create();
