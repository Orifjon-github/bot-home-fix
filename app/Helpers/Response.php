<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

trait Response {
    public function success($data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function error($message, $status = 200): JsonResponse
    {
        return response()->json([
            'success' => false,
            'reason' => $message
        ], $status);
    }
}
