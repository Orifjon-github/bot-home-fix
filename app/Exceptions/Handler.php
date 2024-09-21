<?php

namespace App\Exceptions;

use App\Helpers\Response;
use App\Services\LogService;
use App\Services\Telegram;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Throwable;


class Handler extends ExceptionHandler
{
    use Response;
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function render($request, Throwable $e)
    {
        $notify = new Telegram(new LogService());
        $notify->sendMessage(['chat_id' => '298410462', 'text' => $e->getMessage()]);
        return $this->success(['message' => ':)']);
    }
}
