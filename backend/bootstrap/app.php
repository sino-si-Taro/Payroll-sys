<?php

use App\Http\Middleware\AuthEventLogger;
use App\Http\Middleware\EnsureAdminOnly;
use App\Http\Middleware\EnsureHrOrAdmin;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin_only' => EnsureAdminOnly::class,
            'hr_or_admin' => EnsureHrOrAdmin::class,
        ]);

        // Apply security headers and auth logging to all API requests
        $middleware->api(prepend: [
            SecurityHeaders::class,
            AuthEventLogger::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Only render structured JSON errors for API requests
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'error' => [
                        'code' => 422,
                        'message' => $e->getMessage(),
                        'details' => $e->errors(),
                    ],
                ], 422);
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $model = class_basename($e->getModel());

                return response()->json([
                    'error' => [
                        'code' => 404,
                        'message' => "{$model} not found.",
                    ],
                ], 404);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'error' => [
                        'code' => 401,
                        'message' => $e->getMessage() ?: 'Unauthenticated.',
                    ],
                ], 401);
            }
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'error' => [
                        'code' => 429,
                        'message' => 'Too many requests. Please try again later.',
                    ],
                ], 429);
            }
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'error' => [
                        'code' => $e->getStatusCode(),
                        'message' => $e->getMessage() ?: 'An error occurred.',
                    ],
                ], $e->getStatusCode());
            }
        });

        // Catch-all for unexpected errors (hide internals in production)
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $message = app()->hasDebugModeEnabled()
                    ? $e->getMessage()
                    : 'Internal server error.';

                return response()->json([
                    'error' => [
                        'code' => 500,
                        'message' => $message,
                    ],
                ], 500);
            }
        });
    })->create();
