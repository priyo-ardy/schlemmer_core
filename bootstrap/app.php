<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (Response $response) {
            if ($response->getStatusCode() === 404) {
                return Inertia::render('Errors/Error_404', ['status' => 404])
                    ->toResponse(request())
                    ->setStatusCode(404);
            }

            if ($response->getStatusCode() === 500) {
                return Inertia::render('Errors/Error_500', ['status' => 500])
                    ->toResponse(request())
                    ->setStatusCode(500);
            }
            return $response;
        });
    })->create();
