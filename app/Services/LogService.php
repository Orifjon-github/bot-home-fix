<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class LogService
{
    public function request(string $service, string $id, string $url = null, string $additional = null, array $data = []): void
    {
        $request_code = request()->header('RequestCode');
        Log::channel($service)->info(
            "REQUEST  $request_code - $id $url :$additional",
            $data
        );
    }

    public function response(string $service, string $id, int $response_code, string $additional = null, ?array $data = []): void
    {
        $request_code = request()->header('RequestCode');
        Log::channel($service)->info(
            "RESPONSE $request_code - $id $response_code :$additional",
            $data ?: []
        );
    }
}
