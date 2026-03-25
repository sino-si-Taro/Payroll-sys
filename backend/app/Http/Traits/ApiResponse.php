<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Return a success JSON response.
     */
    protected function success(
        mixed $data = null,
        string $message = '',
        int $status = 200,
        array $meta = []
    ): JsonResponse {
        $response = [];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if ($message !== '') {
            $response['message'] = $message;
        }

        if ($meta !== []) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $status);
    }

    /**
     * Return a paginated success response with meta block.
     */
    protected function paginated(
        \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator,
        string $message = ''
    ): JsonResponse {
        return $this->success(
            data: $paginator->items(),
            message: $message,
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }

    /**
     * Return a structured error JSON response.
     */
    protected function error(
        string $message = 'An error occurred.',
        int $code = 400,
        mixed $details = null
    ): JsonResponse {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if ($details !== null) {
            $error['details'] = $details;
        }

        return response()->json(['error' => $error], $code);
    }
}
