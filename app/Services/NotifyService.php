<?php

namespace App\Services;

use App\Helpers\ConstantHelpers;
use App\Helpers\MainHelper;
use Illuminate\Support\Facades\Http;
use Throwable;

class NotifyService
{
    private string $url;
    private string $token;
    private LogService $log_service;

    public function __construct(LogService $log_service)
    {
        $this->url = config('services.notify_service.url');
        $this->token = config('services.auth_token');
        $this->log_service = $log_service;
    }

    public function telegram($message, $role = 'npgate'): bool
    {
        $data = [
            'role' => $role,
            'message' => $message,
        ];
        return $this->send($data);
    }

    private function send(array $params = []): bool
    {
        $log_id = uniqid();
        $this->log_service->request('notify_service', $log_id, 'telegram-group', null, $params);
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token,
                'requestcode' => request()->header('RequestCode') ?: uniqid(),
                'Service' => 'npgate_service',
            ])
                ->withBody(json_encode($params))
                ->timeout(10)
                ->connectTimeout(5)
                ->withOptions([
                    'proxy' => '',
                    'verify' => false
                ])->send('POST', "$this->url/telegram-group");
            $this->log_service->response('notify_service', $log_id, $response->status() ?? null, null, $response->json());

            if ($response->successful()) {
                return true;
            }
            return false;
        } catch (Throwable $exception) {
            $this->log_service->response('notify_service', $log_id, 500, $exception->getMessage());
            return false;
        }
    }
}
