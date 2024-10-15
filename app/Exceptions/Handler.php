<?php

namespace App\Exceptions;

use App\Helpers\Response;
use App\Services\LogService;
use App\Services\Telegram;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    use Response;
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function render($request, \Throwable $e)
    {
        $errorMessage = sprintf(
            "Error: %s\nFile: %s\nLine: %d",
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        // Send error message to Telegram
        $notify = new Telegram(new LogService());
        $notify->sendMessage(['chat_id' => '298410462', 'text' => $errorMessage]);

        // Return a generic success message (adjust if needed)
        return $this->success(['message' => ''.$e]);
    }
}
