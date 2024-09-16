<?php

namespace App\Exceptions;

use App\Helpers\MainHelper;
use App\Helpers\Response;
use App\Services\LogService;
use App\Services\NotifyService;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    use Response;
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

//    public function render($request, Throwable $e): JsonResponse
//    {
//        $notify = new NotifyService(new LogService());
//        try {
//            $place = explode('/', $e->getFile());
//            $text = MainHelper::formatter('service', 'IBank Support') . "\n" . MainHelper::formatter('place', end($place)) . "\n" . MainHelper::formatter('line', $e->getLine()) . "\n" . MainHelper::formatter('reason', $e->getMessage());
//            $notify->telegram($text);
//            return $this->error('Server error');
//        } catch (Exception $exception) {
//            $notify->telegram('IBank-Support: ' . $exception->getMessage());
//            return $this->error('Server error');
//        }
//    }
}
