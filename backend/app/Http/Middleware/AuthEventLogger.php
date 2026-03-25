<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthEventLogger
{
    /**
     * Auth-related routes to log.
     */
    private const AUTH_ROUTES = [
        'v1/auth/login',
        'v1/auth/change-password',
        'v1/auth/logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $path = $request->path();

        foreach (self::AUTH_ROUTES as $route) {
            if (str_contains($path, $route)) {
                $this->logAuthEvent($request, $response, $route);
                break;
            }
        }

        return $response;
    }

    private function logAuthEvent(Request $request, Response $response, string $route): void
    {
        $status = $response->getStatusCode();
        $email = $request->input('email', 'N/A');
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        $event = match (true) {
            str_contains($route, 'login') && $status >= 200 && $status < 300 => 'LOGIN_SUCCESS',
            str_contains($route, 'login') => 'LOGIN_FAILED',
            str_contains($route, 'change-password') && $status >= 200 && $status < 300 => 'PASSWORD_CHANGED',
            str_contains($route, 'change-password') => 'PASSWORD_CHANGE_FAILED',
            str_contains($route, 'logout') => 'LOGOUT',
            default => 'AUTH_EVENT',
        };

        Log::channel('security')->info($event, [
            'email' => $email,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
