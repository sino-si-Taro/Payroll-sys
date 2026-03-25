<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $actor = $request->user();

        if (! $actor) {
            return new JsonResponse([
                'error' => [
                    'code' => 401,
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        if ($actor->role !== 'admin') {
            return new JsonResponse([
                'error' => [
                    'code' => 403,
                    'message' => 'Access denied. Only Admin can perform this action.',
                ],
            ], 403);
        }

        return $next($request);
    }
}
