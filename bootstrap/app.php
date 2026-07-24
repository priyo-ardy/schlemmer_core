<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use League\Config\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        // api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // $exceptions->render(function (\Exception $e, Request $request) {
        //     // JANGAN GANGGU ValidationException! 
        //     // Biarkan Laravel menangani error validasi form/login secara normal.
        //     if ($e instanceof ValidationException) {
        //         return;
        //     }

        //     // Untuk error umum / exception lain di luar validasi form:
        //     if ($request->inertia()) {
        //         return back()->withErrors([
        //             'error' => $e->getMessage()
        //         ]);
        //     }
        // });

        $exceptions->respond(function (Response $response) {
            if ($response->getStatusCode() === 404) {
                return Inertia::render('Errors/Error_404', ['status' => 404])
                    ->toResponse(request())
                    ->setStatusCode(404);
            }
            return $response;
        });
    })->create();
