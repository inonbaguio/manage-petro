<?php

namespace App\Modules\Shared\Http;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Return a success response.
     */
    public static function success($data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Return an error response.
     */
    public static function error(string $message = 'Error', int $status = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Return a created response.
     */
    public static function created($data = null, string $message = 'Resource created'): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    /**
     * Return a no content response.
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
